<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\BusinessService;
use App\Services\Notifications\NotificationCatalogue;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\RecipientProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecipientProvisionerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * businesses.email and businesses.phone are both unique, so each call gets its own
     * unless a test is asserting on the resulting normalised address and passes one in.
     */
    private function business(array $overrides = []): Business
    {
        static $seq = 0;
        $seq++;

        return Business::factory()->create(array_merge([
            'phone' => sprintf('0772%06d', $seq),
            'email' => "owner{$seq}@example.test",
        ], $overrides));
    }

    private function ownerFor(Business $business, array $overrides = []): User
    {
        static $seq = 0;
        $seq++;

        return User::factory()->create(array_merge([
            'business_id' => $business->id,
            'phone' => sprintf('0773%06d', $seq),
        ], $overrides));
    }

    public function test_it_seeds_the_owner_on_both_channels(): void
    {
        $business = $this->business(['phone' => '0772123456', 'email' => 'owner@example.test']);
        $this->ownerFor($business, ['phone' => '0772123456', 'email' => 'owner@example.test']);

        $result = app(RecipientProvisioner::class)->ensureOwner($business);

        $this->assertSame(2, $result['created']);

        $recipients = NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->get();

        $this->assertCount(2, $recipients);

        $this->assertEqualsCanonicalizing(
            ['owner@example.test', '+256772123456'],
            $recipients->pluck('address')->all()
        );
    }

    public function test_addresses_are_normalised_on_write(): void
    {
        $business = $this->business(['phone' => '0772 123 456', 'email' => '  Owner@Example.TEST ']);
        $this->ownerFor($business, ['phone' => '0772 123 456', 'email' => '  Owner@Example.TEST ']);

        app(RecipientProvisioner::class)->ensureOwner($business);

        $whatsapp = NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('channel', 'whatsapp')
            ->firstOrFail();

        $this->assertSame('+256772123456', $whatsapp->address);

        // Without normalisation on write, the business's spaced form and the owner's
        // plain form would become two rows for one person.
        $this->assertSame(1, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('channel', 'whatsapp')
            ->count());
    }

    public function test_the_owner_recipient_is_business_level_not_branch_level(): void
    {
        $business = $this->business();
        $this->ownerFor($business);

        app(RecipientProvisioner::class)->ensureOwner($business);

        $this->assertSame(0, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotNull('business_branch_id')
            ->count());
    }

    public function test_recipients_start_unverified(): void
    {
        $business = $this->business();
        $this->ownerFor($business);

        app(RecipientProvisioner::class)->ensureOwner($business);

        $this->assertSame(0, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotNull('verified_at')
            ->count());
    }

    public function test_categories_are_the_mandatory_set_only(): void
    {
        $business = $this->business();
        $this->ownerFor($business);

        app(RecipientProvisioner::class)->ensureOwner($business);

        $recipient = NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->firstOrFail();

        // Spelled out rather than compared against the same source the provisioner
        // reads. An earlier version of this test asserted against
        // config('notifications.email.mandatory_categories'), a key that does not
        // exist — so both sides were null and the test passed while the column was
        // being written empty.
        $this->assertSame(['order', 'payment', 'subscription', 'system'], $recipient->categories);

        // And the non-mandatory categories are genuinely absent, which is the whole
        // point: they are opt-in from a fresh business.
        foreach (['inventory', 'report'] as $optIn) {
            $this->assertNotContains($optIn, $recipient->categories);
        }
    }

    public function test_the_mandatory_set_matches_the_catalogue_flags(): void
    {
        // Guards the two against drifting apart.
        $catalogue = app(NotificationCatalogue::class);

        $this->assertSame(
            $catalogue->mandatoryCategories(),
            $catalogue->all() === [] ? [] : collect($catalogue->all())
                ->filter(fn ($e) => ! empty($e['mandatory']))
                ->pluck('category')->unique()->sort()->values()->all()
        );
    }

    public function test_re_running_creates_nothing_new(): void
    {
        $business = $this->business();
        $this->ownerFor($business);

        $provisioner = app(RecipientProvisioner::class);
        $provisioner->ensureOwner($business);
        $second = $provisioner->ensureOwner($business);

        $this->assertSame(0, $second['created']);
        $this->assertSame(2, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->count());
    }

    public function test_an_unnormalisable_phone_skips_whatsapp_but_not_email(): void
    {
        $business = $this->business(['phone' => 'not-a-number', 'email' => 'bad-phone@example.test']);
        $this->ownerFor($business, ['phone' => 'not-a-number']);

        $result = app(RecipientProvisioner::class)->ensureOwner($business);

        $this->assertSame(1, $result['created']);
        $this->assertSame(['whatsapp'], $result['skipped']);

        // Never invent a number: the row is absent, not filled with a guess.
        $this->assertSame(0, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('channel', 'whatsapp')
            ->count());
    }

    public function test_an_unnormalisable_email_skips_email_but_not_whatsapp(): void
    {
        $business = $this->business(['email' => 'not-an-email']);
        $this->ownerFor($business, ['email' => 'not-an-email']);

        $result = app(RecipientProvisioner::class)->ensureOwner($business);

        $this->assertSame(1, $result['created']);
        $this->assertSame(['email'], $result['skipped']);
    }

    public function test_a_changed_phone_replaces_the_old_row_and_keeps_history(): void
    {
        $business = $this->business();
        $owner = $this->ownerFor($business);

        $provisioner = app(RecipientProvisioner::class);
        $provisioner->ensureOwner($business);

        $old = NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('channel', 'whatsapp')
            ->firstOrFail();

        $owner->update(['phone' => '+256700111111']);
        $provisioner->ensureOwner($business);

        $old->refresh();

        // The old row is deactivated, not deleted: it may already carry delivery
        // history, and rewriting the address in place would attribute that history to
        // the new number.
        $this->assertFalse($old->is_active);
        $this->assertTrue($old->exists);

        $this->assertSame(
            '+256700111111',
            NotificationRecipient::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('channel', 'whatsapp')
                ->where('is_active', true)
                ->firstOrFail()
                ->address
        );
    }

    public function test_it_only_deactivates_the_owner_label(): void
    {
        $business = $this->business();
        $owner = $this->ownerFor($business);

        // A manager the business added deliberately.
        NotificationRecipient::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'business_branch_id' => null,
            'label' => NotificationRecipient::LABEL_CUSTOM,
            'channel' => 'whatsapp',
            'address' => '+256700999999',
            'is_active' => true,
            'verified_at' => now(),
        ]);

        $owner->update(['phone' => '+256700111111']);
        app(RecipientProvisioner::class)->ensureOwner($business);

        $this->assertTrue(
            NotificationRecipient::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('label', NotificationRecipient::LABEL_CUSTOM)
                ->firstOrFail()
                ->is_active
        );
    }

    public function test_it_does_not_touch_another_business(): void
    {
        $mine = $this->business();
        $this->ownerFor($mine);
        $theirs = $this->business();
        $this->ownerFor($theirs);

        app(RecipientProvisioner::class)->ensureOwner($mine);

        $this->assertSame(0, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $theirs->id)
            ->count());
    }

    public function test_a_business_with_no_user_still_gets_recipients(): void
    {
        $business = $this->business();

        $result = app(RecipientProvisioner::class)->ensureOwner($business);

        $this->assertSame(2, $result['created']);

        $this->assertNull(
            NotificationRecipient::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->firstOrFail()
                ->user_id
        );
    }

    public function test_it_follows_the_users_details_over_the_business_record(): void
    {
        $business = $this->business(['email' => 'old@example.test', 'phone' => '0772000000']);
        $owner = $this->ownerFor($business, ['email' => 'new@example.test']);

        app(RecipientProvisioner::class)->ensureOwner($business, $owner);

        $this->assertSame(
            'new@example.test',
            NotificationRecipient::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('channel', 'email')
                ->firstOrFail()
                ->address
        );
    }

    public function test_the_backfill_command_is_idempotent(): void
    {
        $business = $this->business();
        $this->ownerFor($business);

        $this->artisan('duukaflow:notifications:backfill-recipients')->assertSuccessful();
        $this->artisan('duukaflow:notifications:backfill-recipients')->assertSuccessful();

        $this->assertSame(2, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->count());
    }

    public function test_the_backfill_dry_run_writes_nothing(): void
    {
        $business = $this->business();
        $this->ownerFor($business);

        $this->artisan('duukaflow:notifications:backfill-recipients --dry-run')
            ->assertSuccessful();

        $this->assertSame(0, NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->count());
    }

    public function test_registration_provisions_recipients(): void
    {
        // The real path, not the provisioner called directly.
        $user = User::factory()->create([
            'email' => 'founder@example.test',
            'phone' => '0772123456',
        ]);

        $country = Country::factory()->create(['iso_alpha2' => 'UG', 'name' => 'Uganda']);

        $business = app(BusinessService::class)->create([
            'name' => 'Acme Ltd',
            'address' => 'Kampala',
            'business_category_id' => BusinessCategory::factory()->create()->id,
            // Called directly rather than through the HTTP route, so this payload is
            // never validated. businesses.country_id is NOT NULL and has no default.
            'country_id' => $country->id,
        ], $user);

        $recipients = NotificationRecipient::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->get();

        $this->assertCount(2, $recipients);
        $this->assertContains('founder@example.test', $recipients->pluck('address')->all());
        $this->assertContains('+256772123456', $recipients->pluck('address')->all());
    }

    public function test_the_provisioned_owner_can_actually_receive(): void
    {
        // Closes the loop: provisioning exists so the dispatcher resolves a recipient
        // rather than logging no_recipient.
        $business = $this->business();
        $this->ownerFor($business);

        app(RecipientProvisioner::class)->ensureOwner($business);

        // Unverified recipients are treated as absent, so a fresh business is
        // deliberately quiet until the first send verifies the address.
        Queue::fake();
        $result = app(NotificationDispatcher::class)->dispatch(
            'registration.welcome',
            $business->id,
            ['business_id' => $business->id],
            ['business_name' => 'Acme', 'trial_ends_at' => '30 Sep 2026'],
            null,
            ['whatsapp']
        );

        $this->assertFalse($result->wasSent());
    }
}
