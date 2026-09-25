<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\SendNotificationJob;
use App\Models\Business;
use App\Models\NotificationDelivery;
use App\Models\NotificationSubscription;
use App\Services\Notifications\ChannelRegistry;
use App\Services\Notifications\NotificationCatalogue;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\TemplateProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Exercises the email channel end to end through the `array` mailer (phpunit's
 * MAIL_MAILER), so these assertions are against the message SES would actually
 * receive: real headers, real rendered body.
 *
 * Not Mail::fake(). The fake records the Mailable object without building it, so
 * List-Unsubscribe headers — the entire compliance point of this layer — were not
 * observable through it at all.
 */
class NotificationMailTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create([
            'phone' => '+256700000001',
            'email' => 'owner@example.test',
        ]);

        app(TemplateProvisioner::class)->ensureForBusiness($this->business->id);

        $this->transport()->flush();
    }

    private function transport()
    {
        return app('mailer')->getSymfonyTransport();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function deliver(string $type, array $values = []): NotificationDelivery
    {
        Queue::fake();

        // Identity is the same superset as the values: the dedupe shapes want entity ids
        // and periods, and passing values as identity keeps each test from having to
        // restate both.
        app(NotificationDispatcher::class)->dispatch(
            $type,
            $this->business->id,
            $values + ['business_id' => $this->business->id],
            $values,
            null,
            ['email'],
            'owner@example.test',
        );

        return NotificationDelivery::withoutGlobalScopes()
            ->where('type', $type)
            ->where('channel', 'email')
            ->latest('id')
            ->firstOrFail();
    }

    private function send(NotificationDelivery $delivery): NotificationDelivery
    {
        $this->transport()->flush();

        (new SendNotificationJob($delivery->id, 'email'))->handle(app(ChannelRegistry::class));

        return $delivery->fresh();
    }

    /**
     * The single message the channel actually emitted.
     */
    private function sentEmail(): Email
    {
        $messages = $this->transport()->messages();

        $this->assertCount(1, $messages, 'Expected exactly one email to have been sent.');

        return $messages->first()->getOriginalMessage();
    }

    private function sentEmails(): array
    {
        return $this->transport()->messages()
            ->map(fn ($m) => $m->getOriginalMessage())
            ->all();
    }

    public function test_it_sends_the_email(): void
    {
        $delivery = $this->deliver('registration.welcome', [
            'business_name' => 'Acme',
            'trial_ends_at' => '30 Sep 2026',
        ]);

        $fresh = $this->send($delivery);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);

        $email = $this->sentEmail();

        $this->assertSame('owner@example.test', $email->getTo()[0]->getAddress());
        $this->assertSame('Welcome to DuukaFlow, Acme', $email->getSubject());
        $this->assertStringContainsString('Your business is live', (string) $email->getHtmlBody());
        $this->assertStringContainsString('30 Sep 2026', (string) $email->getHtmlBody());
    }

    public function test_a_preference_checked_email_carries_one_click_unsubscribe(): void
    {
        $delivery = $this->deliver('report.monthly', [
            'period' => 'August 2026',
            'report_url' => 'https://example.test/reports/aug',
        ]);

        $this->send($delivery);

        $email = $this->sentEmail();

        // Without these the sending domain collects spam complaints, which eventually
        // stops delivery for every customer on it.
        $this->assertTrue($email->getHeaders()->has('List-Unsubscribe'));
        $this->assertSame(
            'List-Unsubscribe=One-Click',
            $email->getHeaders()->get('List-Unsubscribe-Post')?->getBodyAsString()
        );
    }

    public function test_a_transactional_email_carries_no_unsubscribe_header(): void
    {
        $delivery = $this->deliver('payment.failed', [
            'payment_id' => 7,
            'amount' => 'UGX 50,000',
            'reason' => 'Card declined',
            'retry_url' => 'https://example.test/billing/retry',
        ]);

        $this->send($delivery);

        $email = $this->sentEmail();

        // An unsubscribe header on a billing notice lets a mail client stop the
        // customer receiving the notice they need to act on.
        $this->assertFalse($email->getHeaders()->has('List-Unsubscribe'));
        $this->assertFalse($email->getHeaders()->has('List-Unsubscribe-Post'));
    }

    public function test_the_unsubscribe_url_is_signed_and_points_at_the_delivery(): void
    {
        $delivery = $this->deliver('report.monthly', [
            'period' => 'August 2026',
            'report_url' => 'https://example.test/reports/aug',
        ]);

        $this->send($delivery);

        $header = $this->sentEmail()->getHeaders()->get('List-Unsubscribe');
        $url = trim((string) $header->getBodyAsString(), '<>');

        $this->assertStringContainsString("/notifications/unsubscribe/{$delivery->id}", $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        // No `expires`: an unsubscribe grant is meant to be permanent, and a
        // short-lived one would start failing from a customer's inbox years later.
        $this->assertArrayHasKey('signature', $query);
    }

    public function test_the_one_click_endpoint_unsubscribes_the_category(): void
    {
        $delivery = $this->deliver('report.monthly', [
            'period' => 'August 2026',
            'report_url' => 'https://example.test/reports/aug',
        ]);

        $this->send($delivery);

        $this->assertTrue(
            NotificationSubscription::forEmail('owner@example.test')->isSubscribedTo('report'),
            'Precondition: the address starts subscribed to reports.'
        );

        $this->get($this->unsubscribeUrl($delivery))->assertOk();

        $this->assertFalse(
            NotificationSubscription::forEmail('owner@example.test')->isSubscribedTo('report')
        );
    }

    public function test_unsubscribing_from_one_category_keeps_the_others(): void
    {
        $delivery = $this->deliver('report.monthly', [
            'period' => 'August 2026',
            'report_url' => 'https://example.test/reports/aug',
        ]);

        $this->send($delivery);

        $this->get($this->unsubscribeUrl($delivery))->assertOk();

        $subscription = NotificationSubscription::forEmail('owner@example.test');

        // The other opt-out-able category survives. Before this was fixed, the first
        // one-click unsubscribe set unsubscribed_at, which isSubscribedTo() reads as
        // "opted out of everything" — so muting a report also muted every other
        // preference-checked notification.
        $this->assertTrue(
            $subscription->isSubscribedTo('inventory'),
            'Unsubscribing from reports also muted inventory.'
        );

        // And it is not a blanket opt-out.
        $this->assertNull($subscription->unsubscribed_at);
    }

    public function test_a_mandatory_email_still_sends_after_an_opt_out(): void
    {
        // The user-visible guarantee behind the per-category scoping: opting out of
        // reports must not silence the payment-failure notice they need to act on.
        $report = $this->deliver('report.monthly', [
            'period' => 'August 2026',
            'report_url' => 'https://example.test/reports/aug',
        ]);

        $this->send($report);
        $this->get($this->unsubscribeUrl($report))->assertOk();

        $this->transport()->flush();

        $payment = $this->deliver('payment.failed', [
            'payment_id' => 42,
            'amount' => 'UGX 50,000',
            'reason' => 'Card declined',
            'retry_url' => 'https://example.test/billing/retry',
        ]);

        $this->send($payment);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $payment->fresh()->status);
        $this->assertCount(1, $this->transport()->messages());
    }

    public function test_the_one_click_endpoint_rejects_a_missing_signature(): void
    {
        $delivery = $this->deliver('report.monthly', [
            'period' => 'August 2026',
            'report_url' => 'https://example.test/reports/aug',
        ]);

        $this->send($delivery);

        // The signature is the whole authorisation, since mail clients follow the link
        // with no session. Without it, anyone who guessed a delivery id could
        // unsubscribe an arbitrary address.
        $this->get("/notifications/unsubscribe/{$delivery->id}")->assertForbidden();

        $this->assertTrue(
            NotificationSubscription::forEmail('owner@example.test')->isSubscribedTo('report')
        );
    }

    public function test_the_endpoint_refuses_to_unsubscribe_transactional_mail(): void
    {
        $delivery = $this->deliver('subscription.activated', [
            'subscription_id' => 3,
            'payment_id' => 4,
            'plan_name' => 'Growth',
            'ends_at' => '31 Dec 2026',
        ]);

        $this->send($delivery);

        // Built directly rather than read off a header: a transactional mail carries
        // none, which is the behaviour under test.
        $this->get(URL::signedRoute('notifications.unsubscribe', ['delivery' => $delivery->id]))
            ->assertStatus(422);

        $this->assertTrue(
            NotificationSubscription::forEmail('owner@example.test')->isSubscribedTo('subscription')
        );
    }

    public function test_every_email_type_sends_its_own_subject(): void
    {
        $samples = [
            'registration.welcome' => ['business_name' => 'Acme', 'trial_ends_at' => '30 Sep'],
            'subscription.activated' => ['subscription_id' => 1, 'payment_id' => 1, 'plan_name' => 'Growth', 'ends_at' => '31 Dec'],
            'subscription.plan_changed' => ['subscription_id' => 6, 'plan_id' => 2, 'plan_name' => 'Pro', 'effective_at' => 'today'],
            'subscription.renewed' => ['subscription_id' => 2, 'period_start' => '2026-09-01', 'plan_name' => 'Growth', 'ends_at' => '31 Dec'],
            'payment.failed' => ['payment_id' => 3, 'amount' => 'UGX 50,000', 'reason' => 'Declined', 'retry_url' => 'https://x.test/r'],
            'subscription.expired' => ['subscription_id' => 4, 'ends_at' => '01 Jan', 'renew_url' => 'https://x.test/r'],
            'trial.ended' => ['subscription_id' => 5, 'trial_ends_at' => '01 Jan', 'subscribe_url' => 'https://x.test/s'],
            'report.monthly' => ['period' => 'August', 'report_url' => 'https://x.test/r'],
            'quotation.sent' => ['quotation_id' => 8, 'version' => 1, 'quotation_number' => 'QT-1', 'total' => 'UGX 9,000'],
        ];

        $emailTypes = collect(app(NotificationCatalogue::class)->all())
            ->filter(fn ($e) => in_array('email', $e['channels'], true))
            ->keys()
            ->sort()
            ->values()
            ->all();

        // If the catalogue gains an email type, this fails rather than letting it ship
        // with a generic subject line.
        $this->assertEqualsCanonicalizing(array_keys($samples), $emailTypes);

        $subjects = [];

        foreach ($samples as $type => $values) {
            $delivery = $this->deliver($type, $values);
            $this->send($delivery);

            $email = $this->sentEmail();

            $this->assertSame(
                NotificationDelivery::STATUS_SENT,
                $delivery->fresh()->status,
                "No email sent for {$type}"
            );

            $this->assertNotSame('', $email->getSubject(), "Empty subject for {$type}");
            $this->assertStringContainsString(
                'DuukaFlow',
                (string) $email->getHtmlBody(),
                "Body not rendered for {$type}"
            );

            $subjects[$type] = $email->getSubject();
        }

        // Distinct subjects, so a customer can tell the messages apart in an inbox.
        $this->assertCount(count($subjects), array_unique($subjects));
    }

    public function test_the_action_button_appears_when_a_url_is_supplied(): void
    {
        $delivery = $this->deliver('trial.ended', [
            'subscription_id' => 9,
            'trial_ends_at' => '01 Jan',
            'subscribe_url' => 'https://example.test/subscribe',
        ]);

        $this->send($delivery);

        $this->assertStringContainsString(
            'https://example.test/subscribe',
            (string) $this->sentEmail()->getHtmlBody()
        );
    }

    public function test_no_action_button_when_no_url_is_supplied(): void
    {
        $delivery = $this->deliver('subscription.renewed', [
            'subscription_id' => 11,
            'period_start' => '2026-09-01',
            'plan_name' => 'Growth',
            'ends_at' => '31 Dec 2026',
        ]);

        $this->send($delivery);

        $this->assertStringNotContainsString(
            'Subscribe',
            (string) $this->sentEmail()->getHtmlBody()
        );
    }

    public function test_an_invalid_address_is_rejected_not_sent(): void
    {
        $delivery = NotificationDelivery::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'channel' => 'email',
            'category' => 'report',
            'type' => 'report.monthly',
            'recipient_address' => 'not-an-email',
            'status' => NotificationDelivery::STATUS_PENDING,
            'dedupe_key' => 'bad-address-probe',
            'payload' => ['period' => 'August'],
        ]);

        $fresh = $this->send($delivery);

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $fresh->status);
        $this->assertCount(0, $this->transport()->messages());
    }

    public function test_the_from_address_comes_from_config(): void
    {
        config()->set('notifications.email.from_address', 'billing@duukaflow.test');
        config()->set('notifications.email.from_name', 'DuukaFlow Billing');

        $delivery = $this->deliver('registration.welcome', [
            'business_name' => 'Acme',
            'trial_ends_at' => '30 Sep',
        ]);

        $this->send($delivery);

        $from = $this->sentEmail()->getFrom();

        $this->assertSame('billing@duukaflow.test', $from[0]->getAddress());
        $this->assertSame('DuukaFlow Billing', $from[0]->getName());
    }

    public function test_the_reply_to_is_set_when_configured(): void
    {
        config()->set('notifications.email.reply_to', 'support@duukaflow.test');

        $delivery = $this->deliver('registration.welcome', [
            'business_name' => 'Acme',
            'trial_ends_at' => '30 Sep',
        ]);

        $this->send($delivery);

        $this->assertSame('support@duukaflow.test', $this->sentEmail()->getReplyTo()[0]->getAddress());
    }

    public function test_the_message_body_does_not_leak_the_payload(): void
    {
        $delivery = $this->deliver('registration.welcome', [
            'business_name' => 'Acme',
            'trial_ends_at' => '30 Sep',
        ]);

        $this->send($delivery);

        $body = (string) $this->sentEmail()->getHtmlBody();

        // No raw braces, no serialised arrays, no null bytes from a malformed payload.
        $this->assertStringNotContainsString('{{', $body);
        $this->assertStringNotContainsString('Array', $body);
        $this->assertStringNotContainsString("\0", $body);
    }

    private function unsubscribeUrl(NotificationDelivery $delivery): string
    {
        $header = $this->sentEmail()->getHeaders()->get('List-Unsubscribe');

        $this->assertNotNull($header, 'This email carried no unsubscribe header to follow.');

        return trim((string) $header->getBodyAsString(), '<>');
    }
}
