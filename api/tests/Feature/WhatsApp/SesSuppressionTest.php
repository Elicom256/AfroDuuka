<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\ProcessSesSuppressionsJob;
use App\Models\Business;
use App\Models\NotificationDelivery;
use App\Models\NotificationRecipient;
use App\Models\NotificationSubscription;
use App\Services\Ses\NotificationMessageId;
use App\Services\Ses\SnsSignatureVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Bounce and complaint handling.
 *
 * The signature tests sign and verify a real RSA key rather than stubbing the verifier,
 * because the thing being protected here is the signature check itself. A test that
 * mocked SnsSignatureVerifier would pass while the verifier was completely broken.
 */
class SesSuppressionTest extends TestCase
{
    use RefreshDatabase;

    private const TOPIC = 'arn:aws:sns:eu-west-1:1:duukaflow-ses';

    /**
     * A throwaway keypair. Generated per run rather than committed, so the test can
     * never pass against a stale certificate fixture.
     */
    private static ?string $privateKey = null;

    private static ?string $certificateBody = null;

    private function keypair(): void
    {
        if (self::$privateKey !== null) {
            return;
        }

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $privateKey);

        self::$privateKey = $privateKey;

        $csr = openssl_csr_new(['commonName' => 'sns.eu-west-1.amazonaws.com'], $resource, [
            'digest_alg' => 'sha256',
        ]);

        $certificate = openssl_csr_sign($csr, null, $resource, 365, [
            'digest_alg' => 'sha256',
        ]);

        openssl_x509_export($certificate, $certificateBody);

        self::$certificateBody = trim($certificateBody);
    }

    public function test_a_bounce_for_an_unknown_message_id_is_ignored(): void
    {
        // A shared topic reports on mail we did not send. Attaching the event to some
        // other customer's delivery would be worse than dropping it.
        $response = $this->postSigned($this->bounceEvent('someone-elses-message-id'));

        $response->assertOk()->assertJson(['applied' => 0]);
    }

    public function test_a_forged_signature_is_rejected(): void
    {
        // A correctly built envelope with a signature that belongs to nothing. The
        // payload is real and shaped exactly as SNS would send it, so only the
        // cryptographic check can catch this.
        $event = $this->bounceEvent(NotificationMessageId::make(999));

        $event['Signature'] = base64_encode(str_repeat('A', 256));

        $this->postJson('/api/webhooks/ses', $event)->assertStatus(403);

        $this->assertDatabaseCount('notification_deliveries', 0);
    }

    public function test_a_missing_signature_is_rejected(): void
    {
        $event = $this->bounceEvent(NotificationMessageId::make(999));

        unset($event['Signature']);

        $this->postJson('/api/webhooks/ses', $event)->assertStatus(403);
    }

    public function test_a_signature_from_a_different_certificate_is_rejected(): void
    {
        // The attacker controls the body but not AWS's private key, so they sign with
        // their own. The verifier fetches the key from the URL in the message, which is
        // why that URL is restricted to amazonaws.com above.
        $event = $this->bounceEvent(NotificationMessageId::make(999));

        $attacker = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($attacker, $attackerKey);

        openssl_sign(
            $this->stringToSign($event),
            $signature,
            $attackerKey,
            OPENSSL_ALGO_SHA256,
        );

        $event['Signature'] = base64_encode($signature);

        $this->postJson('/api/webhooks/ses', $event)->assertStatus(403);

        $this->assertDatabaseCount('notification_deliveries', 0);
    }

    public function test_a_tampered_message_is_rejected(): void
    {
        // The signature is over the Message field. Valid signature, altered body: this
        // is the attack the whole verifier exists to stop.
        $delivery = $this->delivery();

        $event = $this->bounceEvent(NotificationMessageId::forDelivery($delivery));

        $event['Message'] = json_encode([
            'notificationType' => 'Delivery',
            'mail' => ['messageId' => NotificationMessageId::forDelivery($delivery)],
        ]);

        $this->postJson('/api/webhooks/ses', $event)->assertStatus(403);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $delivery->fresh()->status);
    }

    public function test_a_certificate_hosted_anywhere_but_aws_is_rejected(): void
    {
        // The SigningCertURL is attacker-controlled. If it is followed as-is, the
        // attacker supplies both the key and the signature it "verifies" against.
        $verifier = new SnsSignatureVerifier;

        $this->assertFalse($verifier->isAwsCertificateUrl('https://evil.test/cert.pem'));
        $this->assertFalse($verifier->isAwsCertificateUrl('http://sns.eu-west-1.amazonaws.com/cert.pem'));
        $this->assertFalse($verifier->isAwsCertificateUrl('https://sns.eu-west-1.amazonaws.com.evil.test/c.pem'));
        $this->assertFalse($verifier->isAwsCertificateUrl('https://amazonaws.com/cert.pem'));
        $this->assertFalse($verifier->isAwsCertificateUrl('not a url'));

        $this->assertTrue($verifier->isAwsCertificateUrl('https://sns.eu-west-1.amazonaws.com/SimpleNotificationService-abc.pem'));
    }

    public function test_a_hard_bounce_fails_the_delivery_and_deactivates_the_recipient(): void
    {
        $delivery = $this->delivery();

        $this->postSigned($this->bounceEvent(NotificationMessageId::forDelivery($delivery)))
            ->assertOk()
            ->assertJson(['applied' => 1]);

        $delivery->refresh();

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $delivery->status);
        $this->assertSame('bounced', $delivery->error_code);
        $this->assertNotNull($delivery->failed_at);

        // Deactivating the recipient is what stops the next send. Without it the same
        // dead address is handed to SES on every subsequent notification.
        $recipient = NotificationRecipient::where('address', 'owner@example.test')->sole();
        $this->assertFalse($recipient->is_active);
        $this->assertSame('hard_bounced', $recipient->deactivated_reason);
        $this->assertNotNull($recipient->deactivated_at);
    }

    public function test_a_soft_bounce_does_not_suppress_a_real_customer(): void
    {
        // Greylisting and full mailboxes are transient. Suppressing on these loses a
        // real customer's notifications permanently over a one-off condition.
        $delivery = $this->delivery();

        $this->postSigned($this->event(NotificationMessageId::forDelivery($delivery), [
            'notificationType' => 'Bounce',
            'bounce' => ['bounceType' => 'Transient', 'bounceSubType' => 'MailboxFull'],
        ]))->assertOk();

        $this->assertSame(NotificationDelivery::STATUS_SENT, $delivery->fresh()->status);
        $this->assertTrue(
            NotificationRecipient::where('address', 'owner@example.test')->sole()->is_active
        );
    }

    public function test_a_complaint_also_opts_the_address_out(): void
    {
        // A spam report is not a transient fault. It is also a request not to be mailed,
        // so the opt-out is recorded rather than quietly reversed on the next send.
        $delivery = $this->delivery();

        $this->postSigned($this->event(NotificationMessageId::forDelivery($delivery), [
            'notificationType' => 'Complaint',
            'complaint' => ['complaintFeedbackType' => 'abuse'],
        ]))->assertOk();

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $delivery->fresh()->status);

        $subscription = NotificationSubscription::first();
        $this->assertNotNull($subscription->unsubscribed_at);
        $this->assertFalse(
            NotificationSubscription::forEmail('owner@example.test')->isSubscribedTo('inventory')
        );
    }

    public function test_a_late_event_cannot_resurrect_a_settled_delivery(): void
    {
        $delivery = $this->delivery(['status' => NotificationDelivery::STATUS_FAILED, 'error_code' => 'bounced']);

        // Webmail clients fetch cached copies for days, so an "Opened" can legitimately
        // arrive after a hard bounce. Treating that as proof of delivery would undo the
        // suppression this whole feature exists to apply.
        $this->postSigned($this->event(NotificationMessageId::forDelivery($delivery), [
            'notificationType' => 'Open',
        ]))->assertOk();

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->delivered_at);
    }

    public function test_a_delivery_event_records_the_timestamp(): void
    {
        $delivery = $this->delivery();

        $this->postSigned($this->event(NotificationMessageId::forDelivery($delivery), [
            'notificationType' => 'Delivery',
        ]))->assertOk();

        $this->assertNotNull($delivery->fresh()->delivered_at);
    }

    public function test_a_sns_subscription_confirmation_visits_the_subscribe_url(): void
    {
        // Skipping this is the most common reason a webhook looks dead: the topic was
        // never subscribed, so the endpoint is simply never called.
        Http::fake();

        $event = $this->sign([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => 'sub-1',
            'Token' => 'tok',
            'TopicArn' => self::TOPIC,
            'Message' => 'You have chosen to subscribe.',
            'SubscribeURL' => 'https://sns.eu-west-1.amazonaws.com/confirm?token=tok',
            'Timestamp' => '2026-09-26T10:00:00.000Z',
            'SignatureVersion' => '2',
            'SigningCertURL' => $this->certUrl,
        ]);

        $this->postJson('/api/webhooks/ses', $event)->assertOk();

        Http::assertSent(fn ($r) => str_contains($r->url(), 'confirm?token=tok'));
    }

    public function test_a_bounce_does_not_reach_another_tenants_recipient(): void
    {
        // The webhook has no tenant context at all — SNS is a server, not a user — so
        // every lookup in the applier is scoped off the delivery row rather than off the
        // caller. Two businesses sharing an owner address is the case that catches it:
        // the address is the same, so only the business_id check can tell them apart.
        $delivery = $this->delivery();

        // Not `first()`: the global branch scope makes positional lookups ambiguous,
        // and the whole point of this test is which exact row moved.
        $ourRecipient = NotificationRecipient::where('address', 'owner@example.test')
            ->where('business_id', $delivery->business_id)
            ->sole();

        $otherBusiness = Business::factory()->create();

        $otherRecipient = NotificationRecipient::create([
            'business_id' => $otherBusiness->id,
            'label' => 'owner',
            'channel' => 'email',
            'address' => 'owner@example.test',
            'is_active' => true,
            'verified_at' => now(),
        ]);

        $this->postSigned($this->bounceEvent(NotificationMessageId::forDelivery($delivery)))
            ->assertOk();

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $delivery->fresh()->status);

        $this->assertFalse($ourRecipient->fresh()->is_active);
        $this->assertTrue($otherRecipient->fresh()->is_active);
        $this->assertNull($otherRecipient->fresh()->deactivated_reason);
    }

    #[Test]
    public function the_suppressions_job_settles_a_send_ses_never_confirmed(): void
    {
        config(['notifications.unconfirmed_grace_minutes' => 60]);

        // A send that timed out is parked in `sending` for SES to confirm. If SES never
        // does, the row stays in-flight forever: not counted as failed, so the attempt
        // budget never advances, and invisible to anyone reading the delivery log.
        $stuck = $this->delivery(['status' => NotificationDelivery::STATUS_SENDING]);
        $stuck->forceFill(['created_at' => now()->subHours(3)])->save();

        (new ProcessSesSuppressionsJob)->handle();

        $stuck->refresh();

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $stuck->status);
        $this->assertSame('unconfirmed', $stuck->error_code);
    }

    #[Test]
    public function the_suppressions_job_leaves_a_recent_send_alone(): void
    {
        // The grace period is the whole point. A send from two minutes ago is still
        // legitimately in flight, and settling it would be as wrong as never settling
        // it at all.
        config(['notifications.unconfirmed_grace_minutes' => 60]);

        $recent = $this->delivery(['status' => NotificationDelivery::STATUS_SENDING]);

        (new ProcessSesSuppressionsJob)->handle();

        $this->assertSame(NotificationDelivery::STATUS_SENDING, $recent->fresh()->status);
    }

    // ---------------------------------------------------------------- helpers

    private string $certUrl = 'https://sns.eu-west-1.amazonaws.com/SimpleNotificationService-test.pem';

    private function delivery(array $overrides = []): NotificationDelivery
    {
        $business = Business::factory()->create(['email' => 'owner@example.test']);

        NotificationRecipient::create([
            'business_id' => $business->id,
            'label' => 'owner',
            'channel' => 'email',
            'address' => 'owner@example.test',
            'is_active' => true,
            'verified_at' => now(),
        ]);

        return NotificationDelivery::create(array_merge([
            'business_id' => $business->id,
            'type' => 'report.monthly',
            'channel' => 'email',
            'category' => 'report',
            'recipient_address' => 'owner@example.test',
            'recipient_user_id' => null,
            'recipient_label' => 'owner',
            'subject' => 'Your report',
            'dedupe_key' => 'report:monthly:2026-08',
            'status' => NotificationDelivery::STATUS_SENT,
            'provider_message_id' => NotificationMessageId::make(1),
        ], $overrides));
    }

    /**
     * An SES event wrapped in a correctly signed SNS envelope.
     *
     * Composed and signed in one step on purpose. Signing first and mutating Message
     * afterwards produces a payload the verifier must reject, which is what the
     * tamper test asserts — so a test that built envelopes that way would be asserting
     * against its own broken fixture.
     */
    private function event(string $messageId, array $sesPayload): array
    {
        return $this->sign([
            'Type' => 'NotificationMessage',
            'MessageId' => 'sns-'.sha1($messageId),
            'TopicArn' => self::TOPIC,
            'Message' => $this->encode(array_merge(
                ['mail' => ['messageId' => $messageId]],
                $sesPayload,
            )),
            'Timestamp' => '2026-09-26T10:00:00.000Z',
            'SignatureVersion' => '2',
            'SigningCertURL' => $this->certUrl,
        ]);
    }

    private function bounceEvent(string $messageId): array
    {
        return $this->event($messageId, [
            'notificationType' => 'Bounce',
            'bounce' => ['bounceType' => 'Permanent', 'bounceSubType' => 'General'],
        ]);
    }

    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * Builds a real SNS envelope: signs the exact field layout AWS uses.
     */
    private function sign(array $payload): array
    {
        $this->keypair();

        // The verifier fetches the key from the cache. Priming it here means no test
        // can produce a correctly signed envelope the verifier is then unable to check.
        Cache::put('sns:cert:'.sha1($this->certUrl), self::$certificateBody);

        openssl_sign(
            $this->stringToSign($payload),
            $signature,
            self::$privateKey,
            OPENSSL_ALGO_SHA256,
        );

        $payload['Signature'] = base64_encode($signature);

        return $payload;
    }

    private function stringToSign(array $payload): string
    {
        $fields = $payload['Type'] === 'NotificationMessage'
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $lines = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $lines[] = $field;
            $lines[] = (string) $payload[$field];
        }

        return implode("\n", $lines)."\n";
    }

    private function postSigned(array $event)
    {
        return $this->postJson('/api/webhooks/ses', $event);
    }
}
