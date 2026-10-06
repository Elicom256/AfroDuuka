<?php

namespace Tests\Feature\Dashboard;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A delete endpoint that does nothing must not report success.
 *
 * Three of these were empty method bodies behind real routes, so `DELETE
 * /api/dashboard/branches/{branch}` and `DELETE /api/dashboard/workers/{worker}` both
 * answered 200 while changing nothing — a caller, and the UI that called it, would
 * reasonably conclude the record was gone. They now refuse with 405 and say why.
 *
 * That matters more than it looks for the branch route: sales, purchases and two dozen
 * other tables cascade on business_branch_id, so the moment that method stopped being
 * empty it would have deleted a branch's trading history for real, with no confirmation
 * and no record of it. A refusal cannot do that.
 */
class NoOpDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $executive;

    private BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $this->executive = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create([
                'business_id' => $business->id,
                'name' => 'Executive',
            ])->id,
        ]);

        $this->actingAs($this->executive);
    }

    public function test_deleting_a_branch_is_refused_and_the_branch_survives(): void
    {
        $response = $this->deleteJson("/api/dashboard/branches/{$this->branch->id}");

        $response->assertStatus(405)->assertJsonPath('message', 'Branches cannot be deleted once trading has started. Close the branch instead.');

        $this->assertDatabaseHas('business_branches', ['id' => $this->branch->id]);
    }

    private function makeWorker(): Worker
    {
        $user = User::factory()->create([
            'business_id' => $this->executive->business_id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $this->executive->role_id,
        ]);

        return Worker::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
            'business_id' => $this->executive->business_id,
            'business_branch_id' => $this->branch->id,
        ]);
    }

    public function test_deleting_a_worker_through_the_worker_route_is_refused(): void
    {
        $worker = $this->makeWorker();

        $this->deleteJson("/api/dashboard/workers/{$worker->id}")
            ->assertStatus(405)
            ->assertJsonPath('message', 'Delete the worker through their user account: DELETE /api/users/workers/{user}.');

        $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    }

    public function test_deleting_a_sale_is_refused(): void
    {
        $sale = Sale::create([
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->executive->id,
            'customer_id' => null,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => 'completed',
            'note' => 'baseline',
        ]);

        $this->deleteJson("/api/sales/branch-sales/{$sale->id}")
            ->assertStatus(405)
            ->assertJsonPath('message', 'Sales cannot be deleted once stock movements have been recorded. Reverse the sale or issue a corrected return instead.');

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    /**
     * The real worker delete still works, so the 405 above is a redirect rather than a
     * removal of the capability.
     */
    public function test_the_real_worker_delete_path_still_works(): void
    {
        $worker = User::factory()->create([
            'business_id' => $this->executive->business_id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $this->executive->role_id,
        ]);

        $this->deleteJson("/api/users/workers/{$worker->id}")->assertOk();

        // User uses SoftDeletes, so the row stays with a deleted_at and is hidden from
        // every query. assertDatabaseMissing would still find it.
        $this->assertSoftDeleted('users', ['id' => $worker->id]);
    }

    /**
     * A caller must never be able to read "deleted" out of a response that deleted
     * nothing, whichever way it asks.
     */
    public function test_no_delete_endpoint_answers_ok_without_deleting(): void
    {
        foreach (['branches', 'workers'] as $resource) {
            $response = $this->deleteJson("/api/dashboard/{$resource}/{$this->branch->id}");

            $this->assertNotSame(
                200,
                $response->getStatusCode(),
                "[{$resource}] answered success for a delete that did not happen."
            );
        }

        $this->assertDatabaseHas('business_branches', ['id' => $this->branch->id]);
    }
}
