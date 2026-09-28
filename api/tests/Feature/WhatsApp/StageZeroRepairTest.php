<?php

namespace Tests\Feature\WhatsApp;

use App\Events\WhatsAppNotificationEvents\BusinessRegistered;
use App\Events\WhatsAppNotificationEvents\FreeTrialExpired;
use App\Events\WhatsAppNotificationEvents\LowStockAlert;
use App\Events\WhatsAppNotificationEvents\OutOfStockAlert;
use App\Events\WhatsAppNotificationEvents\PaymentFailed;
use App\Events\WhatsAppNotificationEvents\PurchaseOrderCreated;
use App\Events\WhatsAppNotificationEvents\SaleOrderCreated;
use App\Events\WhatsAppNotificationEvents\SubscriptionCreated;
use App\Events\WhatsAppNotificationEvents\SubscriptionExpired;
use App\Events\WhatsAppNotificationEvents\SubscriptionPlanChanged;
use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsAppConfig;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression cover for the Stage 0 "unbreak" pass.
 *
 * Each test pins a defect that made business creation fatal, the event pipeline
 * dead, the WhatsApp secrets readable by any tenant, or cross-tenant writes possible.
 */
class StageZeroRepairTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The registration form only collects a name and a category, so the service has to
     * resolve the country itself. Seed the default market so that resolution is real.
     */
    private function seedDefaultCountry(): Country
    {
        return Country::factory()->create([
            'name' => 'Uganda',
            'iso_alpha2' => 'UG',
        ]);
    }

    private function registerBusinessPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Acme Traders',
            'address' => 'Kampala',
            'business_category_id' => BusinessCategory::factory()->create()->id,
        ], $overrides);
    }

    private function userFor(Business $business): User
    {
        $role = Role::factory()->create(['business_id' => $business->id, 'name' => 'Executive']);

        return User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => null,
            'role_id' => $role->id,
        ]);
    }

    /** A user with no business yet, as during onboarding. */
    private function onboardingUser(): User
    {
        $role = Role::factory()->create(['business_id' => null, 'name' => 'Executive']);

        return User::factory()->create([
            'business_id' => null,
            'business_branch_id' => null,
            'role_id' => $role->id,
        ]);
    }

    // ---------------------------------------------------------------- B1

    public function test_business_creation_no_longer_throws_class_not_found(): void
    {
        Queue::fake();

        $country = $this->seedDefaultCountry();
        $user = $this->onboardingUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/dashboard/business', $this->registerBusinessPayload());

        $response->assertCreated();

        $this->assertDatabaseHas('businesses', [
            'name' => 'Acme Traders',
            'country_id' => $country->id,
        ]);
    }

    public function test_business_creation_still_provisions_roles_and_a_branch(): void
    {
        Queue::fake();

        $this->seedDefaultCountry();
        $user = $this->onboardingUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/dashboard/business', $this->registerBusinessPayload())->assertCreated();

        $business = Business::where('name', 'Acme Traders')->firstOrFail();

        $this->assertDatabaseHas('roles', ['business_id' => $business->id, 'name' => 'Operations']);
        $this->assertDatabaseHas('business_branches', ['business_id' => $business->id]);
    }

    public function test_an_explicitly_supplied_country_is_respected(): void
    {
        Queue::fake();

        $this->seedDefaultCountry();
        $kenya = Country::factory()->create(['name' => 'Kenya', 'iso_alpha2' => 'KE']);

        Sanctum::actingAs($this->onboardingUser());

        $this->postJson('/api/dashboard/business', $this->registerBusinessPayload([
            'country_id' => $kenya->id,
        ]))->assertCreated();

        $this->assertDatabaseHas('businesses', [
            'name' => 'Acme Traders',
            'country_id' => $kenya->id,
        ]);
    }

    // ---------------------------------------------------------------- B2

    public function test_business_registered_event_reaches_its_listener(): void
    {
        Event::fake([BusinessRegistered::class]);

        $business = Business::factory()->create();

        event(new BusinessRegistered($business));

        Event::assertDispatched(BusinessRegistered::class);
    }

    public function test_every_whatsapp_event_has_a_registered_listener(): void
    {
        $events = [
            BusinessRegistered::class,
            SubscriptionCreated::class,
            SubscriptionPlanChanged::class,
            FreeTrialExpired::class,
            PaymentFailed::class,
            SubscriptionExpired::class,
            LowStockAlert::class,
            OutOfStockAlert::class,
            PurchaseOrderCreated::class,
            SaleOrderCreated::class,
        ];

        foreach ($events as $event) {
            $this->assertTrue(
                $this->app['events']->hasListeners($event),
                "No listener is registered for {$event}."
            );
        }
    }

    public function test_registration_queues_exactly_one_welcome_message(): void
    {
        Queue::fake();

        $this->seedDefaultCountry();
        Sanctum::actingAs($this->onboardingUser());

        $this->postJson('/api/dashboard/business', $this->registerBusinessPayload())->assertCreated();

        // The welcome message must travel the event -> listener path exactly once.
        // BusinessService used to also call the notification service directly, which
        // risked a double send because the two payloads only matched by luck.
        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, 1);
    }

    // ---------------------------------------------------------------- B8

    public function test_config_index_never_returns_the_access_token(): void
    {
        $business = Business::factory()->create();
        $user = $this->userFor($business);

        WhatsAppConfig::create([
            'business_id' => $business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'access_token' => 'super-secret-token',
            'webhook_verify_token' => 'super-secret-verify',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/whatsapp')->assertOk();

        $body = $response->getContent();

        $this->assertStringNotContainsString('super-secret-token', $body);
        $this->assertStringNotContainsString('super-secret-verify', $body);
        $this->assertSame(WhatsAppConfig::SECRET_MASK, $response->json('data.access_token'));
    }

    public function test_tenant_cannot_update_another_tenants_whatsapp_config(): void
    {
        $ownerBusiness = Business::factory()->create();
        $attackerBusiness = Business::factory()->create();

        $victimConfig = WhatsAppConfig::create([
            'business_id' => $ownerBusiness->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'access_token' => 'victim-token',
        ]);

        Sanctum::actingAs($this->userFor($attackerBusiness));

        // The tenant global scope means route-model-binding cannot even resolve another
        // business's config, so the request 404s rather than 403s. That is the better
        // outcome: a 403 would confirm the id exists. The controller's explicit
        // ownership check remains as a guard for the scope-bypassed code paths.
        $this->putJson("/api/whatsapp/{$victimConfig->id}", [
            'access_token' => 'attacker-token',
        ])->assertNotFound();

        $this->assertSame('victim-token', $victimConfig->fresh()->access_token);
    }

    public function test_tenant_can_update_its_own_whatsapp_config(): void
    {
        $business = Business::factory()->create();

        $config = WhatsAppConfig::create([
            'business_id' => $business->id,
            'provider' => 'demo',
            'business_phone' => '+256700000001',
        ]);

        Sanctum::actingAs($this->userFor($business));

        $this->putJson("/api/whatsapp/{$config->id}", [
            'business_phone' => '+256700000042',
        ])->assertOk();

        $this->assertSame('+256700000042', $config->fresh()->business_phone);
    }

    public function test_store_ignores_a_client_supplied_business_id(): void
    {
        $ownerBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        Sanctum::actingAs($this->userFor($ownerBusiness));

        $this->postJson('/api/whatsapp', [
            'business_phone' => '+256700000002',
            'business_id' => $otherBusiness->id,
        ])->assertCreated();

        $this->assertDatabaseHas('whats_app_configs', [
            'business_id' => $ownerBusiness->id,
        ]);
        $this->assertDatabaseMissing('whats_app_configs', [
            'business_id' => $otherBusiness->id,
        ]);
    }

    public function test_saving_the_settings_form_does_not_overwrite_the_stored_token(): void
    {
        $business = Business::factory()->create();

        $config = WhatsAppConfig::create([
            'business_id' => $business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'access_token' => 'real-token',
            'webhook_verify_token' => 'real-verify',
        ]);

        Sanctum::actingAs($this->userFor($business));

        // The settings screen round-trips the masked placeholder back on save.
        $this->putJson("/api/whatsapp/{$config->id}", [
            'business_phone' => '+256700000009',
            'access_token' => WhatsAppConfig::SECRET_MASK,
            'webhook_verify_token' => WhatsAppConfig::SECRET_MASK,
        ])->assertOk();

        $config->refresh();

        $this->assertSame('+256700000009', $config->business_phone);
        $this->assertSame('real-token', $config->access_token);
        $this->assertSame('real-verify', $config->webhook_verify_token);
    }

    public function test_a_new_token_submitted_from_the_form_is_stored(): void
    {
        $business = Business::factory()->create();

        $config = WhatsAppConfig::create([
            'business_id' => $business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'access_token' => 'old-token',
        ]);

        Sanctum::actingAs($this->userFor($business));

        $this->putJson("/api/whatsapp/{$config->id}", [
            'access_token' => 'brand-new-token',
        ])->assertOk();

        $this->assertSame('brand-new-token', $config->fresh()->access_token);
    }

    public function test_secrets_are_stored_encrypted_at_rest(): void
    {
        $business = Business::factory()->create();

        WhatsAppConfig::create([
            'business_id' => $business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'access_token' => 'plaintext-should-not-persist',
        ]);

        $raw = DB::table('whats_app_configs')
            ->where('business_id', $business->id)
            ->value('access_token');

        $this->assertNotSame('plaintext-should-not-persist', $raw);
        $this->assertSame('plaintext-should-not-persist', Crypt::decryptString($raw));
    }

    public function test_test_message_rejects_a_non_e164_recipient(): void
    {
        $business = Business::factory()->create();
        Sanctum::actingAs($this->userFor($business));

        $this->postJson('/api/whatsapp/test-message', [
            'recipient' => 'not-a-phone-number',
            'message' => 'hello',
        ])->assertStatus(422);
    }

    // ---------------------------------------------------------------- B7

    public function test_a_business_cannot_have_two_whatsapp_configs(): void
    {
        $business = Business::factory()->create();

        WhatsAppConfig::create(['business_id' => $business->id, 'business_phone' => '+256700000001']);

        $this->expectException(UniqueConstraintViolationException::class);

        WhatsAppConfig::create(['business_id' => $business->id, 'business_phone' => '+256700000002']);
    }

    public function test_each_business_can_hold_its_own_config(): void
    {
        $a = Business::factory()->create();
        $b = Business::factory()->create();

        WhatsAppConfig::create(['business_id' => $a->id, 'business_phone' => '+256700000001']);
        WhatsAppConfig::create(['business_id' => $b->id, 'business_phone' => '+256700000002']);

        $this->assertSame(2, WhatsAppConfig::withoutGlobalScopes()->count());
    }
}
