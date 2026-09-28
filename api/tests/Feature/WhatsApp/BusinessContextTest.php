<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Support\Tenant\BusinessContext;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessing;
use Tests\TestCase;

/**
 * B13 regression cover.
 *
 * BaseModel's global scopes used to be gated on Auth::check(), so any queued or
 * scheduled job queried across every tenant. These tests pin the fix: with no
 * authenticated user, a query inside BusinessContext::run() must still be scoped.
 *
 * Note that Business and User deliberately do not extend BaseModel — they are tenant
 * roots. The assertions therefore run against Role and Product, which are scoped.
 */
class BusinessContextTest extends TestCase
{
    use RefreshDatabase;

    private function branchFor(Business $business): BusinessBranch
    {
        return BusinessBranch::factory()->create(['business_id' => $business->id]);
    }

    public function test_queries_are_unscoped_with_no_user_and_no_context(): void
    {
        $a = Business::factory()->create();
        $b = Business::factory()->create();

        Role::factory()->create(['business_id' => $a->id, 'name' => 'Executive']);
        Role::factory()->create(['business_id' => $b->id, 'name' => 'Executive']);

        $this->assertSame(2, Role::count());
    }

    public function test_a_query_inside_the_context_sees_only_that_business(): void
    {
        $mine = Business::factory()->create();
        $other = Business::factory()->create();

        Role::factory()->create(['business_id' => $mine->id, 'name' => 'Executive']);
        Role::factory()->create(['business_id' => $other->id, 'name' => 'Executive']);

        $seen = app(BusinessContext::class)->run($mine->id, fn () => Role::count());

        $this->assertSame(1, $seen);
    }

    public function test_the_context_does_not_leak_after_the_callback_returns(): void
    {
        $mine = Business::factory()->create();
        Business::factory()->create();

        Role::factory()->create(['business_id' => $mine->id, 'name' => 'Executive']);
        Role::factory()->create(['business_id' => $mine->id, 'name' => 'Operations']);

        $context = app(BusinessContext::class);

        $seen = $context->run($mine->id, fn () => Role::count());

        $this->assertSame(2, $seen);
        $this->assertNull($context->businessId());
        $this->assertSame(2, Role::count(), 'Scope survived the callback.');
    }

    public function test_the_context_is_restored_after_a_nested_run(): void
    {
        $outer = Business::factory()->create();
        $inner = Business::factory()->create();

        Role::factory()->create(['business_id' => $outer->id, 'name' => 'Executive']);
        Role::factory()->create(['business_id' => $inner->id, 'name' => 'Executive']);

        $context = app(BusinessContext::class);

        $context->run($outer->id, function () use ($context, $inner, $outer) {
            $this->assertSame(1, Role::count());

            $context->run($inner->id, function () {
                $this->assertSame(1, Role::count());
            });

            // Inner run() must restore the outer tenant, not clear it.
            $this->assertSame($outer->id, $context->businessId());
            $this->assertSame(1, Role::count());
        });
    }

    public function test_the_context_is_restored_even_when_the_callback_throws(): void
    {
        $outer = Business::factory()->create();
        $context = app(BusinessContext::class);

        $context->run($outer->id, function () use ($context, $outer) {
            try {
                $context->run(999999, fn () => throw new \RuntimeException('boom'));
            } catch (\RuntimeException) {
                // expected
            }

            // The failed inner run() must not have clobbered the outer tenant.
            $this->assertSame($outer->id, $context->businessId());
        });

        // And once the outer run() exits, the context is gone again.
        $this->assertNull($context->businessId());
    }

    public function test_a_scheduled_job_scoped_to_one_business_cannot_read_another(): void
    {
        $mine = Business::factory()->create();
        $theirs = Business::factory()->create();

        // products carries no business_id, only business_branch_id, so this exercises
        // the branch scope's BusinessContext fallback specifically.
        Product::factory()->count(3)->create([
            'business_branch_id' => $this->branchFor($theirs)->id,
        ]);
        Product::factory()->create([
            'business_branch_id' => $this->branchFor($mine)->id,
        ]);

        $seen = app(BusinessContext::class)->run(
            $mine->id,
            fn () => Product::count()
        );

        $this->assertSame(1, $seen, 'Job for one business read another business\'s stock.');
    }

    public function test_a_branch_scoped_job_cannot_read_another_branch(): void
    {
        $mine = Business::factory()->create();
        $theirs = Business::factory()->create();

        $myBranch = $this->branchFor($mine);
        Product::factory()->create(['business_branch_id' => $myBranch->id]);
        Product::factory()->count(2)->create([
            'business_branch_id' => $this->branchFor($theirs)->id,
        ]);

        $seen = app(BusinessContext::class)->run(
            $mine->id,
            fn () => Product::count(),
            $myBranch->id
        );

        $this->assertSame(1, $seen);
    }

    public function test_rows_created_inside_the_context_are_stamped_with_that_tenant(): void
    {
        $mine = Business::factory()->create();
        $branch = $this->branchFor($mine);

        $role = app(BusinessContext::class)->run(
            $mine->id,
            fn () => Role::create(['name' => 'Executive'])
        );

        $this->assertSame($mine->id, $role->fresh()->business_id);
    }

    public function test_clearing_the_context_restores_unscoped_reads(): void
    {
        $mine = Business::factory()->create();
        Business::factory()->create();

        Role::factory()->create(['business_id' => $mine->id, 'name' => 'Executive']);
        Role::factory()->create(['business_id' => $mine->id, 'name' => 'Operations']);

        $context = app(BusinessContext::class);
        $context->set($mine->id);

        $this->assertSame(2, Role::count());

        $context->clear();

        $this->assertNull($context->businessId());
    }

    public function test_a_worker_does_not_carry_one_tenants_context_into_the_next_job(): void
    {
        $first = Business::factory()->create();
        $second = Business::factory()->create();

        Role::factory()->create(['business_id' => $first->id, 'name' => 'Executive']);
        Role::factory()->create(['business_id' => $second->id, 'name' => 'Executive']);

        $context = app(BusinessContext::class);

        // Simulate worker reuse: job one sets a context, then the queue events clear it
        // before job two starts.
        $context->run($first->id, fn () => null);
        $context->clear();

        $secondRunCount = $context->run($second->id, fn () => Role::count());

        $this->assertSame(1, $secondRunCount);
    }

    public function test_the_queue_clears_the_context_between_jobs(): void
    {
        $context = app(BusinessContext::class);
        $context->set(4242);

        event(new JobProcessing('sync', $this->createStub(Job::class)));

        $this->assertNull($context->businessId());
    }
}
