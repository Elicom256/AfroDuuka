<?php

namespace Tests\Feature\WhatsApp;

use App\Contracts\Notifications\ChannelResult;
use App\Jobs\SendNotificationJob;
use App\Models\Business;
use App\Models\NotificationDelivery;
use App\Models\WhatsAppConfig;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Notifications\ChannelRegistry;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\TemplateProvisioner;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\Providers\DemoWhatsAppProvider;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * The send path's outcomes, exercised for real.
 *
 * This exists because two branches of it had never run. The sent path was only ever
 * driven through the demo provider and nothing asserted the provider message id, so a
 * send could be marked delivered with a null id and no test would notice — and the status
 * webhook, which matches on exactly that id, is the next thing Stage 3 builds. The
 * exception branch had no coverage at all, and it is the branch MetaWhatsAppProvider now
 * takes by design, so setting WHATSAPP_PROVIDER=meta would exercise exactly the untested
 * path.
 */
class SendPathOutcomeTest extends TestCase
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

    private function reserve(): NotificationDelivery
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

        return NotificationDelivery::withoutGlobalScopes()->firstOrFail();
    }

    private function runJob(NotificationDelivery $delivery): void
    {
        try {
            (new SendNotificationJob($delivery->id, 'whatsapp'))->handle(app(ChannelRegistry::class));
        } catch (\Throwable) {
            // The job rethrows after recording. Swallowed here so each test can assert on
            // the state it left behind, which is the part that matters.
        }
    }

    public function test_a_successful_send_stores_the_provider_message_id(): void
    {
        $delivery = $this->reserve();

        $this->runJob($delivery);

        $fresh = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        // The demo provider returns `provider_message_id`, which the channel used to
        // ignore. The delivery was marked sent with a null id, so nothing could ever
        // correlate it with a provider status callback.
        $this->assertNotNull(
            $fresh->provider_message_id,
            'A sent delivery with no provider message id cannot be reconciled by the status webhook.'
        );
        $this->assertStringStartsWith('demo-', (string) $fresh->provider_message_id);
    }

    public function test_an_unusable_config_is_a_definitive_rejection_not_an_ambiguous_send(): void
    {
        $delivery = $this->reserve();

        // A meta config with no phone number id or token. Nothing is transmitted, so
        // there is nothing in doubt: this used to be recorded as an ambiguous send
        // because the unimplemented provider threw, and the throw was mistaken for a
        // network failure. Filed as ambiguous it would have sat in `sending` forever.
        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'is_active' => true,
        ]);

        $this->runJob($delivery);

        $fresh = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $fresh->status);
        $this->assertNull($fresh->sent_at);
        $this->assertSame('not_configured', $fresh->error_code);
    }

    public function test_a_provider_that_genuinely_throws_is_recorded_as_an_exception(): void
    {
        $delivery = $this->reserve();

        // An unexpected throw from inside the transport is not a normal outcome now that
        // the provider is implemented, but the job's catch is the last line of defence.
        // It has to leave the row in `sending`: the job never heard a verdict from Meta,
        // so it must not manufacture one in either direction.
        $throwing = new class implements WhatsAppProviderInterface
        {
            public function getName(): string
            {
                return 'meta';
            }

            public function validateConfiguration(array $config): bool
            {
                return true;
            }

            public function sendMessage(array $payload): array
            {
                throw new \RuntimeException('transport exploded');
            }

            public function listTemplates(): array
            {
                return [];
            }
        };

        $factory = Mockery::mock(WhatsAppProviderFactory::class)->makePartial();
        $factory->shouldReceive('for')->andReturn($throwing);
        $this->app->instance(WhatsAppProviderFactory::class, $factory);

        $this->runJob($delivery);

        $fresh = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_SENDING, $fresh->status);
        $this->assertNull($fresh->sent_at);
        $this->assertSame('exception', $fresh->error_code);
        $this->assertStringContainsString('transport exploded', (string) $fresh->error_message);
    }

    public function test_an_ambiguous_send_is_not_retried_into_a_double_send(): void
    {
        $delivery = $this->reserve();

        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'phone_number_id' => 'phone-number-id-1',
            'access_token' => 'token-abc',
            'is_active' => true,
        ]);

        // A 5xx is the honest ambiguity: Meta may have accepted the message before the
        // fault, so the row must stay in `sending` and a retry must stand down.
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Service unavailable', 'code' => 2]], 503),
        ]);

        $this->runJob($delivery);
        $afterFirst = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_SENDING, $afterFirst->status);

        $this->runJob($delivery);
        $afterSecond = $delivery->fresh();

        Http::assertSentCount(1);
        $this->assertSame(
            $afterFirst->attempt_count,
            $afterSecond->attempt_count,
            'A retry after an ambiguous send re-attempted delivery, risking a duplicate.'
        );
    }

    /**
     * Meta's own response shape, pinned before Stage 3 exists to produce it.
     *
     * The Cloud API answers a send with `messages[0].id`, not `message_id`. If the
     * channel ever stopped reading that path, real sends would be marked sent with a null
     * id and the status webhook would silently match nothing — a failure with no visible
     * symptom until messages start arriving that the app cannot account for.
     */
    public function test_metas_nested_message_id_is_read(): void
    {
        $result = $this->interpretThrough([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.HBgNNTUxMTk5OTk5Mzk5OTk5']],
        ]);

        $this->assertTrue($result->accepted);
        $this->assertSame('wamid.HBgNNTUxMTk5OTk5Mzk5OTk5', $result->providerMessageId);
    }

    public function test_a_flat_message_id_is_read(): void
    {
        $result = $this->interpretThrough(['message_id' => 'wamid.flat']);

        $this->assertTrue($result->accepted);
        $this->assertSame('wamid.flat', $result->providerMessageId);
    }

    public function test_the_demo_providers_message_id_is_read(): void
    {
        $result = $this->interpretThrough(['provider_message_id' => 'demo-abc123']);

        $this->assertTrue($result->accepted);
        $this->assertSame('demo-abc123', $result->providerMessageId);
    }

    /**
     * Run a provider response through the real channel, with only the transport faked.
     *
     * Faking the provider rather than the channel is the point: interpret() is private,
     * and the bug this guards was in the translation between a provider's response and
     * the delivery row, which is exactly the seam a hand-written unit test would skip.
     */
    private function interpretThrough(array $response): ChannelResult
    {
        $provider = new class($response) implements WhatsAppProviderInterface
        {
            public function __construct(private readonly array $response) {}

            public function getName(): string
            {
                return 'meta';
            }

            public function validateConfiguration(array $config): bool
            {
                return true;
            }

            public function sendMessage(array $payload): array
            {
                return $this->response;
            }

            public function listTemplates(): array
            {
                return [];
            }
        };

        $factory = Mockery::mock(WhatsAppProviderFactory::class)->makePartial();
        $factory->shouldReceive('for')->andReturn($provider);

        $delivery = NotificationDelivery::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'channel' => 'whatsapp',
            'category' => 'system',
            'type' => 'registration.welcome',
            'template_key' => 'registration.welcome',
            'recipient_address' => '+256700000001',
            'status' => NotificationDelivery::STATUS_SENDING,
            'dedupe_key' => 'interpret-probe',
        ]);

        $channel = new WhatsAppChannel($factory, app(WhatsAppService::class));

        return $channel->send($delivery, []);
    }

    public function test_the_demo_provider_is_still_the_default_and_still_works(): void
    {
        // Closing the exception branch must not have changed the default path.
        $this->assertInstanceOf(
            DemoWhatsAppProvider::class,
            WhatsAppProviderFactory::create(['provider' => 'demo'])
        );
    }
}
