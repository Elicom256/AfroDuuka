<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\SendNotificationJob;
use App\Models\Business;
use App\Models\NotificationDelivery;
use App\Models\WhatsAppConfig;
use App\Services\Notifications\ChannelRegistry;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\TemplateProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
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

    private function reserve(string $channel = 'whatsapp'): NotificationDelivery
    {
        Queue::fake();

        app(NotificationDispatcher::class)->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            ['business_name' => 'Acme', 'trial_ends_at' => '30 Sep 2026'],
            null,
            [$channel]
        );

        Queue::assertPushed(SendNotificationJob::class);

        return NotificationDelivery::withoutGlobalScopes()->firstOrFail();
    }

    public function test_a_demo_send_moves_pending_to_sent(): void
    {
        $delivery = $this->reserve();

        (new SendNotificationJob($delivery->id, 'whatsapp'))->handle(
            app(ChannelRegistry::class)
        );

        $fresh = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertNotNull($fresh->sent_at);
        $this->assertSame('demo', $fresh->provider);
        $this->assertSame(1, $fresh->attempt_count);
    }

    public function test_an_already_sent_delivery_is_not_resent(): void
    {
        $delivery = $this->reserve();

        $registry = app(ChannelRegistry::class);

        (new SendNotificationJob($delivery->id, 'whatsapp'))->handle($registry);
        (new SendNotificationJob($delivery->id, 'whatsapp'))->handle($registry);

        $this->assertSame(1, $delivery->fresh()->attempt_count, 'A retry double-sent.');
    }

    public function test_an_ambiguous_delivery_is_left_for_the_webhook(): void
    {
        $delivery = $this->reserve();
        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_SENDING,
            'provider_message_id' => null,
        ])->save();

        (new SendNotificationJob($delivery->id, 'whatsapp'))->handle(
            app(ChannelRegistry::class)
        );

        $fresh = $delivery->fresh();

        $this->assertSame(NotificationDelivery::STATUS_SENDING, $fresh->status);
        $this->assertSame(0, $fresh->attempt_count, 'An ambiguous send was retried into a double-send.');
    }

    public function test_an_inactive_config_suppresses_rather_than_sending(): void
    {
        $delivery = $this->reserve();

        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'demo',
            'business_phone' => '+256700000001',
            'is_active' => false,
        ]);

        (new SendNotificationJob($delivery->id, 'whatsapp'))->handle(
            app(ChannelRegistry::class)
        );

        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->fresh()->status);
    }

    public function test_an_unknown_channel_suppresses_instead_of_throwing(): void
    {
        $delivery = $this->reserve();

        (new SendNotificationJob($delivery->id, 'carrier-pigeon'))->handle(
            app(ChannelRegistry::class)
        );

        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->fresh()->status);
    }

    public function test_the_email_channel_accepts_a_valid_address(): void
    {
        $delivery = $this->reserve('email');

        (new SendNotificationJob($delivery->id, 'email'))->handle(
            app(ChannelRegistry::class)
        );

        $this->assertSame(NotificationDelivery::STATUS_SENT, $delivery->fresh()->status);
    }

    public function test_a_send_resolves_the_config_of_its_own_tenant(): void
    {
        $delivery = $this->reserve();

        // A distinctive config on the first business only.
        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'demo',
            'business_phone' => '+256700111111',
            'is_active' => true,
        ]);

        $other = Business::factory()->create(['phone' => '+256700000002']);

        $otherDelivery = NotificationDelivery::withoutGlobalScopes()->create([
            'business_id' => $other->id,
            'channel' => 'whatsapp',
            'category' => 'system',
            'type' => 'registration.welcome',
            'template_key' => 'registration.welcome',
            'recipient_address' => '+256700000002',
            'status' => NotificationDelivery::STATUS_PENDING,
            'dedupe_key' => 'other',
        ]);

        (new SendNotificationJob($otherDelivery->id, 'whatsapp'))->handle(
            app(ChannelRegistry::class)
        );

        // The other business sends, to its own number, off its own config.
        $this->assertSame(NotificationDelivery::STATUS_SENT, $otherDelivery->fresh()->status);
        $this->assertSame('+256700000002', $otherDelivery->fresh()->recipient_address);

        $otherConfig = WhatsAppConfig::withoutGlobalScopes()
            ->where('business_id', $other->id)->firstOrFail();

        $this->assertNotSame('+256700111111', $otherConfig->business_phone);
    }

    public function test_a_deactivated_config_on_one_tenant_does_not_affect_another(): void
    {
        $delivery = $this->reserve();

        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'demo',
            'business_phone' => '+256700111111',
            'is_active' => false,
        ]);

        $other = Business::factory()->create(['phone' => '+256700000002']);

        $otherDelivery = NotificationDelivery::withoutGlobalScopes()->create([
            'business_id' => $other->id,
            'channel' => 'whatsapp',
            'category' => 'system',
            'type' => 'registration.welcome',
            'template_key' => 'registration.welcome',
            'recipient_address' => '+256700000002',
            'status' => NotificationDelivery::STATUS_PENDING,
            'dedupe_key' => 'other',
        ]);

        $registry = app(ChannelRegistry::class);

        (new SendNotificationJob($delivery->id, 'whatsapp'))->handle($registry);
        (new SendNotificationJob($otherDelivery->id, 'whatsapp'))->handle($registry);

        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->fresh()->status);
        $this->assertSame(
            NotificationDelivery::STATUS_SENT,
            $otherDelivery->fresh()->status,
            'One tenant\'s deactivated config suppressed another tenant.'
        );
    }
}
