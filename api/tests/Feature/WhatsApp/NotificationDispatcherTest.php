<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\SendNotificationJob;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\NotificationDelivery;
use App\Models\NotificationRecipient;
use App\Models\NotificationSubscription;
use App\Models\WhatsAppConfig;
use App\Models\WhatsAppTemplate;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\TemplateProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private NotificationDispatcher $dispatcher;

    private Business $business;

    private BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $this->dispatcher = app(NotificationDispatcher::class);
        $this->business = Business::factory()->create(['phone' => '+256700000001', 'email' => 'owner@example.test']);
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        app(TemplateProvisioner::class)->ensureForBusiness($this->business->id);
    }

    private function approveAllTemplates(): void
    {
        WhatsAppTemplate::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->update(['template_status' => 'APPROVED']);
    }

    private function values(): array
    {
        return [
            'business_name' => 'Acme',
            'trial_ends_at' => '30 Sep 2026',
            'plan_name' => 'Pro',
            'ends_at' => '30 Sep 2026',
            'amount' => '50,000',
            'reason' => 'card declined',
            'retry_url' => 'https://example.test/retry',
            'renew_url' => 'https://example.test/renew',
            'subscribe_url' => 'https://example.test/subscribe',
            'days_remaining' => '3',
            'effective_at' => '1 Oct 2026',
            'period' => 'August 2026',
            'report_url' => 'https://example.test/report',
            'product_summary' => 'Rice 25kg (2)',
            'reorder_level' => '5',
        ];
    }

    public function test_it_reserves_a_pending_row_and_queues_the_send(): void
    {
        $result = $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $this->assertTrue($result->wasSent());

        $delivery = NotificationDelivery::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(NotificationDelivery::STATUS_PENDING, $delivery->status);
        $this->assertSame('registration.welcome', $delivery->type);
        $this->assertSame('duukaflow_welcome', $delivery->meta_template_name);
        $this->assertSame('+256700000001', $delivery->recipient_address);
        $this->assertTrue($delivery->is_mandatory);

        Bus::assertDispatched(SendNotificationJob::class);
    }

    public function test_a_duplicate_event_is_dropped_by_the_unique_dedupe_key(): void
    {
        $identity = ['business_id' => $this->business->id];

        $this->dispatcher->dispatch('registration.welcome', $this->business->id, $identity, $this->values(), null, ['whatsapp']);
        $second = $this->dispatcher->dispatch('registration.welcome', $this->business->id, $identity, $this->values(), null, ['whatsapp']);

        $this->assertFalse($second->wasSent());
        $this->assertSame(1, $second->duplicates());
        $this->assertSame(1, NotificationDelivery::withoutGlobalScopes()->count());
    }

    public function test_dedupe_is_scoped_per_channel(): void
    {
        $identity = ['business_id' => $this->business->id];

        $this->dispatcher->dispatch('registration.welcome', $this->business->id, $identity, $this->values(), null, ['whatsapp']);

        // Same event, different transport: must not be treated as a duplicate.
        $email = $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            $identity,
            $this->values(),
            null,
            ['email']
        );

        $this->assertTrue($email->wasSent());
        $this->assertSame(2, NotificationDelivery::withoutGlobalScopes()->count());
    }

    public function test_no_recipient_is_logged_rather_than_guessed(): void
    {
        $bare = Business::factory()->create(['phone' => null, 'email' => null]);
        app(TemplateProvisioner::class)->ensureForBusiness($bare->id);

        $result = $this->dispatcher->dispatch(
            'registration.welcome',
            $bare->id,
            ['business_id' => $bare->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $this->assertFalse($result->wasSent());
        $this->assertContains(NotificationDelivery::REASON_NO_RECIPIENT, $result->suppressionReasons());

        $delivery = NotificationDelivery::withoutGlobalScopes()->where('business_id', $bare->id)->firstOrFail();
        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->status);
        $this->assertNull($delivery->recipient_address);
    }

    public function test_an_unnormalisable_number_is_refused_not_guessed(): void
    {
        $odd = Business::factory()->create(['phone' => 'call me maybe', 'email' => 'o@example.test']);
        app(TemplateProvisioner::class)->ensureForBusiness($odd->id);

        $this->dispatcher->dispatch(
            'registration.welcome',
            $odd->id,
            ['business_id' => $odd->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $this->assertSame(0, NotificationDelivery::withoutGlobalScopes()
            ->where('business_id', $odd->id)
            ->whereNotNull('recipient_address')
            ->count());
    }

    public function test_a_business_level_recipient_is_used_when_one_is_stored(): void
    {
        NotificationRecipient::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'channel' => 'whatsapp',
            'address' => '+256700000009',
            'label' => NotificationRecipient::LABEL_OWNER,
            'verified_at' => now(),
        ]);

        $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $this->assertSame('+256700000009', NotificationDelivery::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->firstOrFail()->recipient_address);
    }

    public function test_an_unverified_stored_recipient_is_treated_as_absent(): void
    {
        NotificationRecipient::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'channel' => 'whatsapp',
            'address' => '+256700000009',
            'verified_at' => null,
        ]);

        $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        // Falls through to the business number, because the stored one is unverified.
        $this->assertSame('+256700000001', NotificationDelivery::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->firstOrFail()->recipient_address);
    }

    public function test_a_mandatory_notification_ignores_an_opt_out(): void
    {
        $subscription = NotificationSubscription::forEmail('owner@example.test');
        $subscription->unsubscribe();

        $result = $this->dispatcher->dispatch(
            'subscription.renewed',
            $this->business->id,
            ['subscription_id' => 3, 'period_start' => '2026-09-01'],
            $this->values(),
            null,
            ['email']
        );

        $this->assertTrue($result->wasSent(), 'A billing notice must not be suppressible.');
    }

    public function test_a_preference_checked_notification_honours_the_opt_out(): void
    {
        NotificationSubscription::forEmail('owner@example.test')->unsubscribe();

        $result = $this->dispatcher->dispatch(
            'report.monthly',
            $this->business->id,
            ['business_id' => $this->business->id, 'period' => '2026-08'],
            $this->values(),
            null,
            ['email']
        );

        $this->assertFalse($result->wasSent());
        $this->assertContains(NotificationDelivery::REASON_OPTED_OUT, $result->suppressionReasons());
    }

    public function test_a_recipient_category_opt_out_is_honoured(): void
    {
        NotificationRecipient::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'channel' => 'whatsapp',
            'address' => '+256700000001',
            'categories' => ['subscription'],
            'verified_at' => now(),
        ]);

        // inventory is preference-checked and not in the allow-list.
        $result = $this->dispatcher->dispatch(
            'inventory.low_stock',
            $this->business->id,
            [
                'business_id' => $this->business->id,
                'business_branch_id' => $this->branch->id,
                'product_id' => 19,
                'alert_episode' => 1,
            ],
            $this->values(),
            $this->branch->id
        );

        $this->assertFalse($result->wasSent());
        $this->assertContains(NotificationDelivery::REASON_OPTED_OUT, $result->suppressionReasons());
    }

    public function test_an_unapproved_template_is_suppressed_not_sent(): void
    {
        // A real Meta provider means approval is required.
        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'is_active' => true,
        ]);

        $result = $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $this->assertFalse($result->wasSent());
        $this->assertContains(
            NotificationDelivery::REASON_TEMPLATE_NOT_APPROVED,
            $result->suppressionReasons()
        );
    }

    public function test_demo_provider_sends_without_a_meta_approval(): void
    {
        $result = $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $this->assertTrue($result->wasSent(), 'Demo mode has no Meta account to approve against.');
    }

    public function test_an_approved_template_renders_and_stores_its_parameters(): void
    {
        $this->approveAllTemplates();
        WhatsAppConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'is_active' => true,
        ]);

        $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $delivery = NotificationDelivery::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(
            ['business_name' => 'Acme', 'trial_ends_at' => '30 Sep 2026'],
            $delivery->payload
        );
    }

    public function test_a_missing_template_variable_suppresses_rather_than_shipping_a_placeholder(): void
    {
        $this->approveAllTemplates();

        $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            ['business_name' => 'Acme'], // trial_ends_at omitted
            null,
            ['whatsapp']
        );

        $delivery = NotificationDelivery::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->status);
    }

    public function test_a_branch_scoped_notification_without_a_branch_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is branch scoped');

        $this->dispatcher->dispatch(
            'inventory.low_stock',
            $this->business->id,
            ['business_id' => $this->business->id, 'product_id' => 1, 'alert_episode' => 1],
            $this->values()
        );
    }

    public function test_an_unknown_type_throws_rather_than_silently_doing_nothing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown notification type');

        $this->dispatcher->dispatch('subscription.exploded', $this->business->id);
    }

    public function test_it_never_touches_another_business(): void
    {
        $other = Business::factory()->create(['phone' => '+256700000002', 'email' => 'other@example.test']);
        app(TemplateProvisioner::class)->ensureForBusiness($other->id);

        $this->dispatcher->dispatch(
            'registration.welcome',
            $this->business->id,
            ['business_id' => $this->business->id],
            $this->values(),
            null,
            ['whatsapp']
        );

        $this->assertSame(0, NotificationDelivery::withoutGlobalScopes()
            ->where('business_id', $other->id)->count());
    }

    public function test_an_explicit_address_overrides_recipient_resolution(): void
    {
        $this->dispatcher->dispatch(
            'quotation.sent',
            $this->business->id,
            ['quotation_id' => 14, 'version' => 2],
            $this->values(),
            null,
            ['email'],
            'customer@example.test'
        );

        $this->assertSame(
            'customer@example.test',
            NotificationDelivery::withoutGlobalScopes()->firstOrFail()->recipient_address
        );
    }
}
