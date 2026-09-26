<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\SendNotificationJob;
use App\Models\Business;
use App\Models\NotificationDelivery;
use App\Models\WhatsAppConfig;
use App\Services\Notifications\ChannelRegistry;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\TemplateProvisioner;
use App\Services\WhatsApp\Providers\MetaWhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * POST /{version}/{phone-number-id}/messages.
 *
 * The assertion this file exists to make is not that the request is well-formed — it is
 * that every way the call can end lands in the right delivery state. A send has three
 * outcomes, and the failure mode of getting one wrong is a customer either messaged twice
 * or never messaged at all, both of which are silent.
 */
class MetaWhatsAppSendTest extends TestCase
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
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function provider(array $overrides = []): MetaWhatsAppProvider
    {
        return new MetaWhatsAppProvider($overrides + [
            'access_token' => 'token-abc',
            'phone_number_id' => 'phone-number-id-1',
        ]);
    }

    /**
     * @param  array<int, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function payload(array $parameters = ['Acme'], string $to = '+256700000001'): array
    {
        return [
            'to' => $to,
            // The channel passes our own notification key here, not a Meta message type.
            'type' => 'registration.welcome',
            'template' => [
                'name' => 'welcome_acme',
                'language' => 'en',
                'parameters' => $parameters,
            ],
            'business_id' => $this->business->id,
        ];
    }

    private function accept(): void
    {
        Http::fake([
            '*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '+256700000001', 'wa_id' => '256700000001']],
                'messages' => [['id' => 'wamid.HBgNNTUxMTk5OTk1Mzk1']],
            ], 200),
        ]);
    }

    public function test_a_send_posts_the_template_message_body(): void
    {
        $this->accept();

        $this->provider()->sendMessage($this->payload(['Acme', '30 Sep 2026']));

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            $this->assertSame('whatsapp', $body['messaging_product']);
            $this->assertSame('individual', $body['recipient_type']);
            $this->assertSame('+256700000001', $body['to']);

            // The message type is literally `template`. The channel puts our notification
            // key in payload['type']; forwarding it would be rejected by every request.
            $this->assertSame('template', $body['type']);
            $this->assertNotSame('registration.welcome', $body['type']);

            $this->assertSame('welcome_acme', $body['template']['name']);
            $this->assertSame(['code' => 'en'], $body['template']['language']);

            $this->assertSame('body', $body['template']['components'][0]['type']);
            $this->assertSame(
                [
                    ['type' => 'text', 'text' => 'Acme'],
                    ['type' => 'text', 'text' => '30 Sep 2026'],
                ],
                $body['template']['components'][0]['parameters']
            );

            return true;
        });
    }

    public function test_a_send_omits_components_entirely_when_the_template_has_no_placeholders(): void
    {
        $this->accept();

        $this->provider()->sendMessage($this->payload([]));

        Http::assertSent(function (Request $request) {
            // An empty `components` array is a 400 against a placeholder-less template.
            $this->assertArrayNotHasKey('components', $request->data()['template']);
            $this->assertArrayNotHasKey('components', $request->data()['template'] ?? []);

            return true;
        });
    }

    public function test_the_send_targets_the_configured_number_on_the_configured_version(): void
    {
        config(['services.whatsapp.graph_api_version' => 'v25.0']);

        $this->accept();

        $this->provider()->sendMessage($this->payload());

        Http::assertSent(function (Request $request) {
            $this->assertSame('https://graph.facebook.com/v25.0/phone-number-id-1/messages', $request->url());
            $this->assertSame('POST', $request->method());
            $this->assertSame('Bearer token-abc', $request->header('Authorization')[0]);

            return true;
        });
    }

    public function test_a_successful_send_returns_metas_own_message_id(): void
    {
        $this->accept();

        $response = $this->provider()->sendMessage($this->payload());

        $this->assertSame('wamid.HBgNNTUxMTk5OTk1Mzk1', $response['messages'][0]['id']);
        $this->assertArrayNotHasKey('error', $response);
    }

    public function test_a_recipient_is_normalised_to_e164_at_the_boundary(): void
    {
        $this->accept();

        // 0700123456 is the local form a Ugandan contact would actually have stored.
        $this->provider()->sendMessage($this->payload(['Acme'], '0700123456'));

        Http::assertSent(function (Request $request) {
            $this->assertSame('+256700123456', $request->data()['to']);

            return true;
        });
    }

    public function test_an_unusable_recipient_is_refused_without_calling_meta(): void
    {
        Http::fake();

        $response = $this->provider()->sendMessage($this->payload(['Acme'], 'call me maybe'));

        $this->assertSame('invalid_recipient', $response['code']);
        // Nothing was transmitted, so nothing is in doubt: a rejection, not an ambiguous
        // send. Throwing here would have left the row awaiting a webhook that cannot exist.
        $this->assertArrayNotHasKey('messages', $response);
        Http::assertNothingSent();
    }

    public function test_a_missing_template_name_is_refused_without_calling_meta(): void
    {
        Http::fake();

        $response = $this->provider()->sendMessage([
            'to' => '+256700000001',
            'template' => ['name' => '', 'language' => 'en', 'parameters' => []],
        ]);

        $this->assertSame('no_template', $response['code']);
        Http::assertNothingSent();
    }

    public function test_an_incomplete_config_is_refused_without_calling_meta(): void
    {
        Http::fake();

        // validateConfiguration() passes on any one of three fields, so a config with only
        // a business phone is considered valid and still cannot address a send.
        $response = (new MetaWhatsAppProvider(['business_phone' => '+256700000001']))
            ->sendMessage($this->payload());

        $this->assertSame('not_configured', $response['code']);
        Http::assertNothingSent();
    }

    public function test_a_template_rejection_kemetas_own_code(): void
    {
        // 132012: the parameter count no longer matches the approved template. A bug in
        // our payload, not a transient fault, so Meta's code is the actionable part.
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Number of parameters does not match the expected number of params',
                    'type' => 'OAuthException',
                    'code' => 132012,
                    'error_data' => ['details' => 'Expected 1, got 2.'],
                ],
            ], 400),
        ]);

        $response = $this->provider()->sendMessage($this->payload());

        $this->assertSame('132012', $response['code']);
        $this->assertStringContainsString('Number of parameters does not match', $response['error']);
        $this->assertStringContainsString('Expected 1, got 2.', $response['error']);
    }

    public function test_an_expired_token_is_reported_as_an_auth_failure(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $response = $this->provider()->sendMessage($this->payload());

        $this->assertSame('auth', $response['code']);
    }

    public function test_a_rate_limit_is_a_rejection_and_not_an_ambiguous_send(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Application request limit reached',
                    'type' => 'OAuthException',
                    'code' => 4,
                ],
            ], 429, ['Retry-After' => '30']),
        ]);

        $response = $this->provider()->sendMessage($this->payload());

        // Meta answered, so nothing was sent and no status webhook will ever mention this
        // message. Filing it as ambiguous would strand it in `sending` with no way out.
        $this->assertSame('throttled', $response['code']);
        $this->assertNotSame('timeout', $response['code']);
        $this->assertSame('30', $response['retry_after']);
    }

    public function test_a_server_fault_is_ambiguous_because_meta_may_have_accepted_it(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => ['message' => 'Service unavailable', 'code' => 2],
            ], 503),
        ]);

        $response = $this->provider()->sendMessage($this->payload());

        // The one answered failure that is still not definitive. `timeout` is the code the
        // channel reads as "do not know, do not retry".
        $this->assertSame('timeout', $response['code']);
    }

    public function test_a_dead_connection_is_ambiguous(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out after 30001 milliseconds');
        });

        $response = $this->provider()->sendMessage($this->payload());

        $this->assertSame('connection_error', $response['code']);
    }

    // ---- The classification, observed through the delivery row it produces ----

    private function reserveMetaDelivery(): NotificationDelivery
    {
        Queue::fake();

        app(NotificationDispatcher::class)->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            ['business_name' => 'Acme', 'trial_ends_at' => '30 Sep 2026'],
            null,
            ['whatsapp']
        );

        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'phone_number_id' => 'phone-number-id-1',
            'access_token' => 'token-abc',
            'is_active' => true,
        ]);

        return NotificationDelivery::withoutGlobalScopes()->firstOrFail();
    }

    private function runJob(NotificationDelivery $delivery): void
    {
        try {
            (new SendNotificationJob($delivery->id, 'whatsapp'))->handle(app(ChannelRegistry::class));
        } catch (\Throwable) {
            // The job rethrows after recording. What matters is the state it left.
        }
    }

    public function test_a_real_send_is_recorded_as_sent_with_metas_message_id(): void
    {
        $delivery = $this->reserveMetaDelivery();

        $this->accept();

        $this->runJob($delivery);

        $fresh = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertSame('meta', $fresh->provider);
        // The id the status webhook will match on. Null here means every delivery
        // reconciles to nothing, silently.
        $this->assertSame('wamid.HBgNNTUxMTk5OTk1Mzk1', $fresh->provider_message_id);
        $this->assertNotNull($fresh->sent_at);
    }

    public function test_a_throttled_send_is_failed_rather_than_stranded_in_sending(): void
    {
        $delivery = $this->reserveMetaDelivery();

        Http::fake([
            '*' => Http::response([
                'error' => ['message' => 'Application request limit reached', 'code' => 4],
            ], 429, ['Retry-After' => '30']),
        ]);

        $this->runJob($delivery);

        $fresh = $delivery->fresh();

        // The distinction that decides whether this message is recoverable. `sending`
        // would mean "wait for a webhook", and no webhook will ever describe a message
        // Meta never accepted — so the notification would be lost with no error shown.
        $this->assertSame(NotificationDelivery::STATUS_FAILED, $fresh->status);
        $this->assertNull($fresh->sent_at);
        $this->assertSame('throttled', $fresh->error_code);
    }

    public function test_a_5xx_send_is_left_for_the_webhook_and_not_retried(): void
    {
        $delivery = $this->reserveMetaDelivery();

        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Service unavailable', 'code' => 2]], 503),
        ]);

        $this->runJob($delivery);
        $afterFirst = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_SENDING, $afterFirst->status);

        $this->runJob($delivery);
        $afterSecond = $delivery->fresh();

        // Meta may have accepted the first attempt, so the retry has to stand down.
        Http::assertSentCount(1);
        $this->assertSame($afterFirst->attempt_count, $afterSecond->attempt_count);
    }

    public function test_a_success_with_no_readable_message_id_is_left_unresolved(): void
    {
        $delivery = $this->reserveMetaDelivery();

        // A 2xx with a body that is not a send response. Meta accepted the request, so
        // the message probably went out, but there is no id for the webhook to match.
        Http::fake(['*' => Http::response('<html>not json</html>', 200)]);

        $this->runJob($delivery);

        $fresh = $delivery->fresh();

        // Unresolved, not failed. Failing it would invite a resend of a message Meta
        // probably accepted, and marking it sent would claim a delivery we cannot
        // correlate. Leaving it in `sending` is the honest middle.
        $this->assertSame(NotificationDelivery::STATUS_SENDING, $fresh->status);
        $this->assertNull($fresh->sent_at);
        $this->assertNull($fresh->provider_message_id);
        $this->assertSame('unreadable_response', $fresh->error_code);
    }

    public function test_a_template_rejection_is_failed_and_not_ambiguous(): void
    {
        $delivery = $this->reserveMetaDelivery();

        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'Number of parameters does not match',
                    'code' => 132012,
                ],
            ], 400),
        ]);

        $this->runJob($delivery);

        $fresh = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $fresh->status);
        $this->assertSame('132012', $fresh->error_code);
    }
}
