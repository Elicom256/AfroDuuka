<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\NotificationDelivery;
use App\Models\NotificationRecipient;
use App\Models\NotificationSubscription;
use App\Support\Tenant\BusinessContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationModelTest extends TestCase
{
    use RefreshDatabase;

    private function businessWithBranch(): array
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        return [$business, $branch];
    }

    public function test_two_different_addresses_can_each_have_a_subscription(): void
    {
        // Regression: a non-null '' token default made the second address collide
        // with the first on the token unique index.
        $a = NotificationSubscription::forEmail('one@example.test');
        $b = NotificationSubscription::forEmail('two@example.test');

        $this->assertNotSame($a->id, $b->id);
        $this->assertNull($a->token);
        $this->assertNull($b->token);
    }

    public function test_for_email_is_case_insensitive_and_reuses_the_row(): void
    {
        $first = NotificationSubscription::forEmail('Owner@Example.test');
        $again = NotificationSubscription::forEmail('  owner@EXAMPLE.test  ');

        $this->assertSame($first->id, $again->id);
        $this->assertSame('owner@example.test', $again->email);
        $this->assertSame(1, NotificationSubscription::count());
    }

    public function test_the_stored_token_is_hashed_and_the_raw_value_still_resolves(): void
    {
        $subscription = NotificationSubscription::forEmail('owner@example.test');
        $raw = $subscription->issueToken();

        $subscription->refresh();

        $this->assertNotSame($raw, $subscription->token, 'Raw token was stored in the clear.');
        $this->assertSame(hash('sha256', $raw), $subscription->token);
        $this->assertTrue(NotificationSubscription::findByToken($raw)?->is($subscription));
        $this->assertNull(NotificationSubscription::findByToken('wrong-token'));
        $this->assertNull(NotificationSubscription::findByToken(null));
    }

    public function test_issuing_a_token_twice_invalidates_the_first_link(): void
    {
        $subscription = NotificationSubscription::forEmail('owner@example.test');

        $first = $subscription->issueToken();
        $second = $subscription->issueToken();

        $this->assertNull(NotificationSubscription::findByToken($first), 'A leaked link stayed valid after rotation.');
        $this->assertNotNull(NotificationSubscription::findByToken($second));
    }

    public function test_category_preferences_match_between_recipient_and_subscription(): void
    {
        $subscription = NotificationSubscription::forEmail('owner@example.test');
        [$business] = $this->businessWithBranch();

        $recipient = NotificationRecipient::create([
            'business_id' => $business->id,
            'label' => NotificationRecipient::LABEL_OWNER,
            'channel' => NotificationRecipient::CHANNEL_EMAIL,
            'address' => 'owner@example.test',
            'categories' => ['inventory', 'stock'],
        ]);

        $this->assertTrue($recipient->canReceive('inventory'));
        $this->assertFalse($recipient->canReceive('payment'));

        $subscription->forceFill(['categories' => ['inventory', 'stock']])->save();
        $this->assertTrue($subscription->isSubscribedTo('inventory'));
        $this->assertFalse($subscription->isSubscribedTo('payment'));

        // An empty list means "no preferences expressed", so everything is allowed.
        $recipient->forceFill(['categories' => []])->save();
        $this->assertTrue($recipient->canReceive('payment'));
    }

    public function test_an_unverified_recipient_is_not_deliverable(): void
    {
        [$business, $branch] = $this->businessWithBranch();

        $pending = NotificationRecipient::create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'channel' => NotificationRecipient::CHANNEL_WHATSAPP,
            'address' => '+256700000001',
        ]);

        $this->assertFalse($pending->isVerified());
        $this->assertFalse($pending->isActive());
        $this->assertSame(0, $pending->deliverable()->count());

        $pending->forceFill(['verified_at' => now()])->save();
        $this->assertTrue($pending->isActive());
        $this->assertSame(1, $pending->deliverable()->count());
    }

    public function test_the_two_unique_indexes_allow_branch_and_business_level_rows(): void
    {
        [$business, $branch] = $this->businessWithBranch();

        NotificationRecipient::create([
            'business_id' => $business->id,
            'channel' => NotificationRecipient::CHANNEL_WHATSAPP,
            'address' => '+256700000002',
        ]);

        NotificationRecipient::create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'channel' => NotificationRecipient::CHANNEL_WHATSAPP,
            'address' => '+256700000002',
        ]);

        $this->assertSame(2, NotificationRecipient::count());

        $this->expectException(UniqueConstraintViolationException::class);

        NotificationRecipient::create([
            'business_id' => $business->id,
            'channel' => NotificationRecipient::CHANNEL_WHATSAPP,
            'address' => '+256700000002',
        ]);
    }

    public function test_suppression_cannot_overwrite_a_delivered_send(): void
    {
        [$business] = $this->businessWithBranch();

        $delivery = NotificationDelivery::create([
            'business_id' => $business->id,
            'channel' => 'whatsapp',
            'category' => 'inventory',
            'type' => 'stock.low',
            'template_key' => 'inventory.stock.low',
            'status' => NotificationDelivery::STATUS_DELIVERED,
            'dedupe_key' => 'stock-low:1:1',
        ]);

        $this->assertFalse($delivery->markSuppressed(NotificationDelivery::REASON_OPTED_OUT));
        $this->assertSame(NotificationDelivery::STATUS_DELIVERED, $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->suppressed_reason);
    }

    public function test_a_pending_delivery_records_its_suppression_reason(): void
    {
        [$business] = $this->businessWithBranch();

        $delivery = NotificationDelivery::create([
            'business_id' => $business->id,
            'channel' => 'email',
            'category' => 'payment',
            'type' => 'payment.received',
            'template_key' => 'payment.received',
            'status' => NotificationDelivery::STATUS_PENDING,
            'dedupe_key' => 'payment-received:1',
        ]);

        $this->assertTrue($delivery->markSuppressed(NotificationDelivery::REASON_OPTED_OUT));
        $this->assertSame(NotificationDelivery::REASON_OPTED_OUT, $delivery->fresh()->suppressed_reason);
        $this->assertNotNull($delivery->fresh()->suppressed_at);
    }

    public function test_the_same_event_may_reserve_once_per_channel(): void
    {
        // Eight catalogue notifications are E + W, so one event legitimately produces
        // two delivery rows. A unique index on dedupe_key alone would suppress one.
        [$business] = $this->businessWithBranch();

        $shared = [
            'business_id' => $business->id,
            'category' => 'system',
            'type' => 'registration.welcome',
            'template_key' => 'registration.welcome',
            'dedupe_key' => 'registration:welcome:business-1',
        ];

        $email = NotificationDelivery::create([...$shared, 'channel' => 'email']);
        $whatsapp = NotificationDelivery::create([...$shared, 'channel' => 'whatsapp']);

        $this->assertNotSame($email->id, $whatsapp->id);
        $this->assertSame(2, NotificationDelivery::withoutGlobalScopes()->count());

        $this->expectException(UniqueConstraintViolationException::class);

        NotificationDelivery::create([...$shared, 'channel' => 'email']);
    }

    public function test_the_dedupe_key_rejects_a_second_identical_send(): void
    {
        [$business] = $this->businessWithBranch();

        $attributes = [
            'business_id' => $business->id,
            'channel' => 'whatsapp',
            'category' => 'inventory',
            'type' => 'stock.low',
            'template_key' => 'inventory.stock.low',
            'dedupe_key' => 'stock-low:9:2',
        ];

        NotificationDelivery::create($attributes);

        $this->expectException(UniqueConstraintViolationException::class);

        NotificationDelivery::create($attributes);
    }

    public function test_deliveries_are_tenant_scoped_inside_a_job(): void
    {
        $mine = Business::factory()->create();
        $theirs = Business::factory()->create();

        // Business-level rows: business_branch_id stays NULL, which is the normal
        // shape for a subscription, payment or security notification.
        NotificationDelivery::create([
            'business_id' => $mine->id,
            'channel' => 'whatsapp',
            'category' => 'subscription',
            'type' => 'subscription.expiring',
            'template_key' => 'subscription.expiring',
            'dedupe_key' => 'a',
        ]);

        NotificationDelivery::create([
            'business_id' => $theirs->id,
            'channel' => 'whatsapp',
            'category' => 'subscription',
            'type' => 'subscription.expiring',
            'template_key' => 'subscription.expiring',
            'dedupe_key' => 'b',
        ]);

        $seen = app(BusinessContext::class)->run($mine->id, fn () => NotificationDelivery::count());

        $this->assertSame(1, $seen, 'A job for one business read another business\'s delivery log.');
    }

    public function test_a_business_sees_its_own_branchless_rows_even_with_branches(): void
    {
        // Regression: whereIn('business_branch_id', [...]) never matches NULL, so
        // every business-wide notification became invisible to its own tenant.
        [$mine, $mineBranch] = $this->businessWithBranch();
        [$theirs, $theirBranch] = $this->businessWithBranch();

        NotificationDelivery::create([
            'business_id' => $mine->id,
            'channel' => 'email',
            'category' => 'payment',
            'type' => 'payment.received',
            'template_key' => 'payment.received',
            'dedupe_key' => 'mine-null',
        ]);

        NotificationDelivery::create([
            'business_id' => $mine->id,
            'business_branch_id' => $mineBranch->id,
            'channel' => 'email',
            'category' => 'inventory',
            'type' => 'stock.low',
            'template_key' => 'inventory.stock.low',
            'dedupe_key' => 'mine-branch',
        ]);

        NotificationDelivery::create([
            'business_id' => $theirs->id,
            'channel' => 'email',
            'category' => 'payment',
            'type' => 'payment.received',
            'template_key' => 'payment.received',
            'dedupe_key' => 'theirs-null',
        ]);

        $seen = app(BusinessContext::class)->run(
            $mine->id,
            fn () => NotificationDelivery::pluck('dedupe_key')->sort()->values()->all()
        );

        $this->assertSame(
            ['mine-branch', 'mine-null'],
            $seen,
            'Business-level (NULL branch) deliveries were hidden from their own tenant.'
        );
    }

    public function test_a_branch_scoped_job_does_not_see_a_sibling_branchs_rows(): void
    {
        [$mine] = $this->businessWithBranch();
        $branchA = BusinessBranch::factory()->create(['business_id' => $mine->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $mine->id]);

        NotificationDelivery::create([
            'business_id' => $mine->id,
            'business_branch_id' => $branchA->id,
            'channel' => 'whatsapp',
            'category' => 'inventory',
            'type' => 'stock.low',
            'template_key' => 'inventory.stock.low',
            'dedupe_key' => 'a',
        ]);

        NotificationDelivery::create([
            'business_id' => $mine->id,
            'business_branch_id' => $branchB->id,
            'channel' => 'whatsapp',
            'category' => 'inventory',
            'type' => 'stock.low',
            'template_key' => 'inventory.stock.low',
            'dedupe_key' => 'b',
        ]);

        // Business-wide rows stay visible; only the sibling branch is hidden.
        NotificationDelivery::create([
            'business_id' => $mine->id,
            'channel' => 'whatsapp',
            'category' => 'payment',
            'type' => 'payment.received',
            'template_key' => 'payment.received',
            'dedupe_key' => 'business-wide',
        ]);

        $seen = app(BusinessContext::class)->run(
            $mine->id,
            fn () => NotificationDelivery::pluck('dedupe_key')->sort()->values()->all(),
            $branchA->id
        );

        $this->assertSame(['a', 'business-wide'], $seen);
    }
}
