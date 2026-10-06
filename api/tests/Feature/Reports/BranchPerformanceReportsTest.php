<?php

namespace Tests\Feature\Reports;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Role;
use App\Models\User;
use App\Services\AnalyticsTrendHelper;
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

    /**
     * The instant every test in this file pretends it is.
     *
     * These tests ask for `period=last_30_days`, which AnalyticsTrendHelper resolves as
     * now()->subDays(30) to now(). That window moves with the calendar, so a fixture dated
     * with a literal quietly fell out of it as time passed: the fixture was 2026-09-05, the
     * window opened on 2026-09-06 on 6 October, and four tests began failing with no code
     * change at all — an empty branch list where rows were expected.
     *
     * Freezing the clock here makes the window a fixed range, so the fixture date below can
     * stay a literal and be read at a glance. Both halves matter: an absolute date paired
     * with a moving window is the trap, so the window is the thing that gets pinned.
     */
    private const NOW = '2026-09-14 10:00:00';

    /**
     * A date comfortably inside `last_30_days` relative to NOW: 9 days back, against a
     * window that starts 30 days back. Far enough in to survive a change to the helper's
     * arithmetic, and the same distance `test_it_excludes_cash_flows_outside_the_period`
     * already used.
     */
    private const IN_PERIOD = '2026-09-05';

    private static int $cashFlowCounter = 0;

    private Business $business;

    private BusinessBranch $kampala;

    private BusinessBranch $jinja;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::NOW);

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

    protected function tearDown(): void
    {
        // setTestNow is global state that outlives the test if it is left in place, and a
        // later test that trusts the real clock would then read a frozen one. Clearing it
        // here keeps that failure out of tests that have nothing to do with this file.
        Carbon::setTestNow();

        parent::tearDown();
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
        // The clock is already frozen at self::NOW by setUp, so the three cases below are
        // read against a known window: IN_PERIOD is inside it, the 2025 row is a year
        // outside, and the pending row is inside by date but excluded by status.
        $this->cashFlow($this->kampala, 'sale', 500_000, self::IN_PERIOD);
        $this->cashFlow($this->kampala, 'sale', 800_000, '2025-06-05');
        $this->cashFlow($this->kampala, 'sale', 300_000, self::IN_PERIOD, ['status' => 'pending']);

        $this->getJson('/api/reports/branch-performance?period=last_30_days')
            ->assertOk()
            ->assertJsonPath('data.branches.0.total_revenue', 500000);
    }

    /**
     * Guards the guard.
     *
     * The bug this freezes the clock to prevent was a fixture falling out of a moving
     * window, which fails as an empty result rather than as an error — so nothing about a
     * failure pointed at the calendar. This asserts the fixture is genuinely inside the
     * window this file's clock produces, which is what makes the other assertions in it
     * mean anything.
     */
    public function test_the_fixture_date_sits_inside_the_requested_period(): void
    {
        $window = app(AnalyticsTrendHelper::class)->getPeriodDates('last_30_days');
        $start = Carbon::parse($window['start'])->startOfDay();
        $end = Carbon::parse($window['end'])->endOfDay();
        $fixture = Carbon::parse(self::IN_PERIOD);

        // assertBetween would say this in one line, but its failure message reports the two
        // bounds rather than the date that broke them, and a date drifting out of a window
        // is exactly the failure that is hard to read.
        $this->assertTrue(
            $fixture->between($start, $end),
            sprintf(
                'The default fixture date %s is outside last_30_days (%s to %s). This file '
                .'freezes the clock precisely so that it cannot drift, so either the fixture '
                .'or self::NOW is wrong.',
                self::IN_PERIOD,
                $start->toDateString(),
                $end->toDateString()
            )
        );
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
        string $date = self::IN_PERIOD,
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
