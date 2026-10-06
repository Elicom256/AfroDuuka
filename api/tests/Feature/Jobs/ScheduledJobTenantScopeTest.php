<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckNotificationsJob;
use App\Jobs\ProcessSubscriptionLifecycleWhatsAppJob;
use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Tenant\BusinessContext;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The two scheduled jobs that sweep the whole install, run with two businesses seeded.
 *
 * Neither job had any test at all, which is how both came to depend on every query naming
 * business_id by hand. That is a real defence — the queries are correct today — but it is
 * invisible: a query added later without the column would read the install and nothing
 * would say so.
 *
 * Both are platform sweeps, so the tenant cannot be set once around the whole job: the
 * first business would be processed and the rest silently skipped. The tenant is per row,
 * and that is what these tests pin — along with the thing the wrap actually buys, which is
 * that a query without an explicit filter is scoped by the context rather than by luck.
 */
class ScheduledJobTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Business $first;

    private Business $second;

    private BusinessBranch $firstBranch;

    private BusinessBranch $secondBranch;

    private User $firstAdmin;

    private User $secondAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->first = Business::factory()->create(['name' => 'Nakatomi Trading']);
        $this->second = Business::factory()->create(['name' => 'Riverstone Retail']);

        $this->firstBranch = BusinessBranch::factory()->create([
            'business_id' => $this->first->id,
            'name' => 'Kampala Road',
        ]);
        $this->secondBranch = BusinessBranch::factory()->create([
            'business_id' => $this->second->id,
            'name' => 'Jinja Main Street',
        ]);

        // The job selects recipients by the literal role name 'Executive'.
        $this->firstAdmin = $this->executiveIn($this->first, $this->firstBranch);
        $this->secondAdmin = $this->executiveIn($this->second, $this->secondBranch);
    }

    private function executiveIn(Business $business, BusinessBranch $branch): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => Role::factory()->create([
                'business_id' => $business->id,
                'name' => 'Executive',
            ])->id,
            'status' => 'active',
        ]);
    }

    /**
     * Runs the callback and reports which tenants were established as it went.
     *
     * Asserting on observed behaviour alone cannot tell "scoped by context" from "scoped
     * because the query happened to name business_id" — the outcome is the same either way.
     * This records the wrap itself, which is the thing worth pinning, and the behavioural
     * assertions alongside it cover the outcome.
     *
     * @param  Closure(): mixed  $callback
     * @return array{0: mixed, 1: array<int, int>}
     */
    private function recordTenantContext(Closure $callback): array
    {
        $spy = new class extends BusinessContext
        {
            /** @var array<int, int> */
            public array $established = [];

            public function run(int $businessId, Closure $callback, ?int $branchId = null): mixed
            {
                $this->established[] = $businessId;

                return parent::run($businessId, $callback, $branchId);
            }
        };

        $this->app->instance(BusinessContext::class, $spy);

        $result = $callback();

        return [$result, $spy->established];
    }

    // ------------------------------------------------ CheckNotificationsJob

    /**
     * Two businesses, two low-stock products, one admin each. Each admin must hear about
     * their own product and nothing about their neighbour's.
     */
    public function test_low_stock_alerts_reach_only_the_business_that_owns_the_product(): void
    {
        $this->lowStockProduct($this->firstBranch, 'Nakatomi Phone');
        $this->lowStockProduct($this->secondBranch, 'Riverstone Phone');

        (new CheckNotificationsJob)->handle(app(NotificationService::class));

        $firstAlerts = Notification::where('user_id', $this->firstAdmin->id)->get();
        $secondAlerts = Notification::where('user_id', $this->secondAdmin->id)->get();

        $this->assertCount(1, $firstAlerts, 'The first business must be told about its own product.');
        $this->assertStringContainsString('Nakatomi Phone', $firstAlerts->first()->message);

        $this->assertCount(1, $secondAlerts, 'The second business must be told about its own product.');
        $this->assertStringContainsString('Riverstone Phone', $secondAlerts->first()->message);
    }

    public function test_the_sweep_runs_inside_each_business_rather_than_around_the_whole_job(): void
    {
        $this->lowStockProduct($this->firstBranch, 'Nakatomi Phone');
        $this->lowStockProduct($this->secondBranch, 'Riverstone Phone');

        [, $established] = $this->recordTenantContext(
            fn () => (new CheckNotificationsJob)->handle(app(NotificationService::class))
        );

        $this->assertEqualsCanonicalizing(
            [$this->first->id, $this->second->id],
            $established,
            'Each business must be entered on its own. One wrap around the whole job would '
            .'scope the sweep to a single tenant and silently stop alerting the rest.'
        );
    }

    /**
     * What the wrap actually buys.
     *
     * products carries no business_id column at all — the tenant there rests entirely on
     * EffectiveBranchScope, which applies no constraint whatsoever when there is neither an
     * authenticated user nor a context. So an unfiltered Product query returns a different
     * number inside the wrap than outside it, and that difference is the guarantee.
     */
    public function test_a_product_query_without_a_business_filter_is_scoped_by_the_context(): void
    {
        $this->lowStockProduct($this->firstBranch, 'Nakatomi Phone');
        $this->lowStockProduct($this->secondBranch, 'Riverstone Phone');

        // Plain Product::count(), naming no tenant — exactly how a query written inside a
        // check that had forgotten the branch filter would read. Not withoutGlobalScopes():
        // that would strip the branch scope too, and with nothing left for the context to
        // drive it would return 2 in both cases and prove nothing.
        $this->assertSame(2, Product::count(), 'Both products exist.');

        $withinFirst = app(BusinessContext::class)->run(
            $this->first->id,
            fn () => Product::count()
        );

        $this->assertSame(
            1,
            $withinFirst,
            'Inside a business context, an unfiltered product query must not see the '
            .'neighbouring tenant.'
        );
    }

    public function test_an_overdue_customer_of_another_business_is_never_reported(): void
    {
        $this->overdueCustomer($this->firstBranch, $this->firstAdmin, 'Nakatomi Credit Customer');

        (new CheckNotificationsJob)->handle(app(NotificationService::class));

        $this->assertSame(
            0,
            Notification::where('user_id', $this->secondAdmin->id)->count(),
            'The second business has no overdue customer, so nothing may be sent to it.'
        );
    }

    private function lowStockProduct(BusinessBranch $branch, string $name): Product
    {
        return Product::factory()->create([
            'business_branch_id' => $branch->id,
            'name' => $name,
            // The job asks for quantity > 0 AND quantity <= reorder_level.
            'quantity' => 3,
            'reorder_level' => 10,
        ]);
    }

    private function overdueCustomer(BusinessBranch $branch, User $owner, string $name): Customer
    {
        $customer = Customer::factory()->create([
            // The factory makes its own business and user and generates a code, so both
            // the tenant and the unique key are pinned here instead.
            'business_id' => $branch->business_id,
            'business_branch_id' => $branch->id,
            'user_id' => $owner->id,
            'customer_code' => 'CUS-'.substr(md5($name), 0, 8),
            // Customer::name() reads company_name first and falls back to the owning
            // user's name, so the label the job prints comes from here.
            'company_name' => $name,
        ]);

        // No SaleFactory exists, so the row is built here. forceCreate because
        // business_id is not mass assignable on Sale — BaseModel's creating hook stamps it
        // from the context, and this test is deliberately running with no context.
        Sale::forceCreate([
            'business_id' => $branch->business_id,
            'business_branch_id' => $branch->id,
            'user_id' => $owner->id,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'subtotal' => 50000,
            'total_amount' => 50000,
            'created_at' => now()->subDays(40),
        ]);

        return $customer;
    }

    // ------------------------- ProcessSubscriptionLifecycleWhatsAppJob

    private function lapsedSubscriptionFor(Business $business, string $name): Subscription
    {
        $business->forceFill(['phone' => $name === 'first' ? '+256700000001' : '+256700000002'])->save();

        return Subscription::factory()->create([
            'business_id' => $business->id,
            'plan_id' => Plan::factory()->create()->id,
            'status' => 'expired',
            'starts_at' => now()->subMonths(3),
            'ends_at' => now()->subDays(10),
        ]);
    }

    /**
     * The lifecycle job does not send; it queues a ProcessWhatsAppNotificationJob per
     * message. So the assertion is on the payloads, and the queue is faked so the nested
     * job never runs and no log row is written.
     */
    public function test_an_expired_subscription_queues_a_message_for_its_own_business(): void
    {
        Queue::fake();

        $this->lapsedSubscriptionFor($this->first, 'first');
        $this->lapsedSubscriptionFor($this->second, 'second');

        (new ProcessSubscriptionLifecycleWhatsAppJob)->handle();

        $queued = $this->queuedBusinessIds();

        $this->assertNotEmpty($queued, 'Both lapsed subscriptions should have queued a message.');

        foreach ($queued as $businessId) {
            $this->assertContains(
                $businessId,
                [$this->first->id, $this->second->id],
                'A message was queued for a business that owns no lapsed subscription.'
            );
        }

        // Every business with a lapsed subscription must be represented, which is the
        // property a single wrap around handle() would destroy.
        $this->assertEqualsCanonicalizing(
            [$this->first->id, $this->second->id],
            array_values(array_unique($queued))
        );
    }

    public function test_the_queue_fake_is_actually_capturing_the_nested_dispatch(): void
    {
        Queue::fake();

        $this->lapsedSubscriptionFor($this->first, 'first');

        (new ProcessSubscriptionLifecycleWhatsAppJob)->handle();

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class);
    }

    public function test_the_lifecycle_sweep_establishes_each_business_on_its_own(): void
    {
        Queue::fake();

        $this->lapsedSubscriptionFor($this->first, 'first');
        $this->lapsedSubscriptionFor($this->second, 'second');

        [, $established] = $this->recordTenantContext(
            fn () => (new ProcessSubscriptionLifecycleWhatsAppJob)->handle()
        );

        $this->assertEqualsCanonicalizing(
            [$this->first->id, $this->second->id],
            $established
        );
    }

    /**
     * The sweep must find both businesses even though it is walking inside a context for
     * each one. A wrap that leaked out of the loop, or that the query inherited, would
     * narrow the sweep to whichever tenant was entered first and the second business's
     * expiry would never be chased.
     */
    public function test_a_broken_wrap_would_narrow_the_sweep_and_lose_a_tenant(): void
    {
        Queue::fake();

        $this->lapsedSubscriptionFor($this->first, 'first');
        $this->lapsedSubscriptionFor($this->second, 'second');

        // The query as it is written, with no context at all: both tenants.
        $both = Subscription::withoutGlobalScopes()
            ->where('status', '!=', 'active')
            ->pluck('business_id')
            ->all();

        $this->assertEqualsCanonicalizing([$this->first->id, $this->second->id], $both);

        // The same query inside one tenant's context: one tenant. This is what a single
        // wrap around handle() would have produced.
        $narrowed = app(BusinessContext::class)->run(
            $this->first->id,
            fn () => Subscription::query()
                ->where('status', '!=', 'active')
                ->pluck('business_id')
                ->all()
        );

        $this->assertSame(
            [$this->first->id],
            $narrowed,
            'Inside a context the query sees one tenant, which is why the wrap belongs '
            .'inside the loop and the candidate query stays outside it.'
        );
    }

    public function test_a_business_without_a_phone_is_skipped_rather_than_messaged(): void
    {
        Queue::fake();

        $this->lapsedSubscriptionFor($this->first, 'first');
        $this->lapsedSubscriptionFor($this->second, 'second');

        // Cleared after the subscription exists, because lapsedSubscriptionFor() is what
        // gives the business a number to send to.
        $this->second->forceFill(['phone' => null])->save();

        (new ProcessSubscriptionLifecycleWhatsAppJob)->handle();

        $this->assertNotContains(
            $this->second->id,
            $this->queuedBusinessIds(),
            'There is nowhere to send to, so nothing may be queued for that business.'
        );
    }

    /**
     * business_id of every notification the lifecycle job queued.
     *
     * QueueFake records pushes as a map of class name => list of entries, where each
     * entry is ['job' => ..., 'queue' => ..., 'data' => ...]. So the job object is under
     * 'job', and iterating as [$class, $entries] would destructure the first entry
     * instead of the key.
     *
     * @return array<int, mixed>
     */
    private function queuedBusinessIds(): array
    {
        $ids = [];

        foreach (Queue::pushedJobs() as $class => $entries) {
            if ($class !== ProcessWhatsAppNotificationJob::class) {
                continue;
            }

            foreach ($entries as $entry) {
                $ids[] = $entry['job']->payload['business_id'] ?? null;
            }
        }

        return $ids;
    }

    /**
     * Context must not survive the job. A worker is a long-lived process, and AppServiceProvider
     * clears on the queue events, but a job called directly — by a test, or by schedule:run
     * invoking handle() in the same process — must not leave one tenant behind it either.
     */
    public function test_no_context_survives_the_sweep(): void
    {
        Queue::fake();

        $this->lapsedSubscriptionFor($this->first, 'first');
        $this->lapsedSubscriptionFor($this->second, 'second');

        (new ProcessSubscriptionLifecycleWhatsAppJob)->handle();

        $this->assertFalse(
            app(BusinessContext::class)->hasBusiness(),
            'A tenant leaked out of the job and into the rest of the process.'
        );
    }

    public function test_no_context_survives_the_notifications_sweep(): void
    {
        $this->lowStockProduct($this->firstBranch, 'Nakatomi Phone');
        $this->lowStockProduct($this->secondBranch, 'Riverstone Phone');

        (new CheckNotificationsJob)->handle(app(NotificationService::class));

        $this->assertFalse(app(BusinessContext::class)->hasBusiness());
    }
}
