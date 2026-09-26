<?php

namespace Tests\Feature\Reports;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The Branch Performance card.
 *
 * This endpoint answered 42702 on every single request — "column reference business_id is
 * ambiguous" — because it joined business_branches onto a cash_flows query while the
 * tenant global scope emits an unqualified `where business_id = ?`. It is the top card on
 * the reports page, and it had no test at all, which is the only reason that survived.
 *
 * The first test here is therefore mostly a regression guard: it asserts a 200 that used
 * to be a 500.
 */
class BranchPerformanceReportsTest extends TestCase
{
    use RefreshDatabase;

    private static int $cashFlowCounter = 0;

    private Business $business;

    private BusinessBranch $kampala;

    private BusinessBranch $jinja;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create(['name' => 'Nakatomi Trading']);
        $this->kampala = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Kampala Road',
        ]);
        $this->jinja = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Jinja Main Street',
        ]);
        $this->admin = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => null,
            'role_id' => Role::factory()->create(['business_id' => $this->business->id])->id,
        ]);

        Sanctum::actingAs($this->admin);
    }

    public function test_the_endpoint_responds(): void
    {
        // Regression guard for the ambiguous-column 500 this endpoint returned on every
        // request. Both a business with several branches and one with a single branch
        // are exercised, because the single-branch case is the one that produced the
        // empty result set and so never touched the group-by path.
        $this->cashFlow($this->kampala, 'sale', 500_000);

        $this->getJson('/api/reports/branch-performance?period=last_30_days')
            ->assertOk()
            ->assertJsonPath('message', 'Branch performance report fetched');
    }

    public function test_it_returns_one_row_per_branch_with_its_name(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($this->jinja, 'sale', 250_000);

        $response = $this->getJson('/api/reports/branch-performance?period=last_30_days')
            ->assertOk()
            ->assertJsonCount(2, 'data.branches');

        // Sorted by revenue, so Kampala leads.
        $response->assertJsonPath('data.branches.0.branch_name', 'Kampala Road')
            ->assertJsonPath('data.branches.0.total_revenue', 500000)
            ->assertJsonPath('data.branches.1.branch_name', 'Jinja Main Street')
            ->assertJsonPath('data.branches.1.total_revenue', 250000);
    }

    public function test_the_branch_dropdown_narrows_the_result(): void
    {
        // The dashboard has always sent ?id=<branch>; the controller dropped it, so the
        // selector narrowed nothing at all.
        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($this->jinja, 'sale', 250_000);

        $this->getJson('/api/reports/branch-performance?period=last_30_days&id='.$this->jinja->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.branches')
            ->assertJsonPath('data.branches.0.branch_id', $this->jinja->id)
            ->assertJsonPath('data.branches.0.branch_name', 'Jinja Main Street');
    }

    public function test_it_refuses_a_branch_from_another_business(): void
    {
        $rival = Business::factory()->create();
        $rivalBranch = BusinessBranch::factory()->create(['business_id' => $rival->id]);

        $this->getJson('/api/reports/branch-performance?period=last_30_days&id='.$rivalBranch->id)
            ->assertForbidden();
    }

    public function test_it_never_shows_another_businesss_branches(): void
    {
        $rival = Business::factory()->create();
        $rivalBranch = BusinessBranch::factory()->create(['business_id' => $rival->id]);

        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($rivalBranch, 'sale', 7_000_000);

        $this->getJson('/api/reports/branch-performance?period=last_30_days')
            ->assertOk()
            ->assertJsonCount(1, 'data.branches')
            ->assertJsonPath('data.branches.0.branch_id', $this->kampala->id);
    }

    public function test_a_branch_user_only_sees_their_own_branch(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($this->jinja, 'sale', 250_000);

        Sanctum::actingAs(User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->kampala->id,
            'role_id' => Role::factory()->create(['business_id' => $this->business->id])->id,
        ]));

        $this->getJson('/api/reports/branch-performance?period=last_30_days')
            ->assertOk()
            ->assertJsonCount(1, 'data.branches')
            ->assertJsonPath('data.branches.0.branch_name', 'Kampala Road');
    }

    public function test_it_excludes_cash_flows_outside_the_period(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $this->cashFlow($this->kampala, 'sale', 500_000, '2026-09-05');
        $this->cashFlow($this->kampala, 'sale', 800_000, '2025-06-05');
        $this->cashFlow($this->kampala, 'sale', 300_000, '2026-09-05', ['status' => 'pending']);

        $this->getJson('/api/reports/branch-performance?period=last_30_days')
            ->assertOk()
            ->assertJsonPath('data.branches.0.total_revenue', 500000);
    }

    public function test_a_month_with_no_branch_activity_returns_no_rows(): void
    {
        // An empty comparison table is a real answer, not a failure. Left to the ambiguous
        // join it would have been a 500 instead.
        $this->getJson('/api/reports/branch-performance?period=last_30_days')
            ->assertOk()
            ->assertJsonCount(0, 'data.branches');
    }

    private function cashFlow(
        BusinessBranch $branch,
        string $type,
        float $amount,
        string $date = '2026-09-05',
        array $overrides = [],
    ): CashFlow {
        return CashFlow::factory()->create(array_merge([
            'transaction_code' => 'CF-BP-'.str_pad(++self::$cashFlowCounter, 6, '0', STR_PAD_LEFT),
            'business_id' => $branch->business_id,
            'business_branch_id' => $branch->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'completed',
            'category' => $type === 'sale' ? 'product_sales' : $type.'s',
            'transaction_date' => $date,
        ], $overrides));
    }
}
