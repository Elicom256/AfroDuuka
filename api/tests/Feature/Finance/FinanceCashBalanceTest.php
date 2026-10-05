<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Role;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cash balance is derived from the cash_flows ledger, so it can never drift out of
 * step with the rows underneath it.
 *
 * It used to be a stored column written by FinanceService::runningBalance(), which
 * nothing called, so every row stayed null and the dashboard reported 0 for every
 * business. These tests pin the behaviour that matters instead: a sale raises the
 * balance, a refund lowers it, and undoing either puts it back.
 */
class FinanceCashBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessBranch $branch;

    private BusinessBranch $otherBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $this->otherBranch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $business->id])->id,
        ]);

        $this->actingAs($this->user);
    }

    private function inflow(string $type, float $amount, ?BusinessBranch $branch = null, ?string $code = null): CashFlow
    {
        return CashFlow::create([
            'transaction_code' => $code ?? 'CF-'.uniqid(),
            'type' => $type,
            'amount' => $amount,
            'currency' => 'UGX',
            'business_id' => $this->user->business_id,
            'business_branch_id' => ($branch ?? $this->branch)->id,
            'category' => 'product_sales',
            'payment_method' => 'cash',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);
    }

    private function dashboard(?string $branchId = null): array
    {
        return app(FinanceService::class)->dashboard($branchId);
    }

    public function test_a_sale_raises_the_cash_balance(): void
    {
        $this->inflow('sale', 500000);

        $this->assertEquals(500000.0, $this->dashboard()['cash_balance']);
    }

    /**
     * The user's case. A refund is a real outflow, so it has to lower the balance the
     * moment it is inserted, with no separate "recalculate" step to forget.
     */
    public function test_a_refund_lowers_the_cash_balance(): void
    {
        $this->inflow('sale', 500000);

        $this->inflow('refund', 200000, code: 'CF-SR-000001');

        $dashboard = $this->dashboard();

        $this->assertEquals(200000.0, $dashboard['total_refunds']);
        $this->assertEquals(300000.0, $dashboard['cash_balance']);
    }

    public function test_deleting_a_refund_gives_the_balance_back(): void
    {
        $this->inflow('sale', 500000);
        $refund = $this->inflow('refund', 200000, code: 'CF-SR-000001');

        $this->assertEquals(300000.0, $this->dashboard()['cash_balance']);

        $refund->forceDelete();

        $this->assertEquals(500000.0, $this->dashboard()['cash_balance'], 'A removed refund must stop reducing the balance.');
    }

    public function test_replacing_a_refund_does_not_double_count_it(): void
    {
        // Editing a return force-deletes the old refund and writes a new one. A
        // balance built by appending deltas would drift here unless the new row
        // replaced the old rather than added to it.
        $this->inflow('sale', 500000);
        $refund = $this->inflow('refund', 200000, code: 'CF-SR-000001');

        $refund->forceDelete();
        $this->inflow('refund', 100000, code: 'CF-SR-000002');

        $this->assertEquals(400000.0, $this->dashboard()['cash_balance']);
    }

    public function test_outflows_reduce_the_balance(): void
    {
        $this->inflow('sale', 500000);
        $this->inflow('purchase', 120000, code: 'CF-P-000001');
        $this->inflow('expense', 30000, code: 'CF-E-000001');
        $this->inflow('payment_in', 70000, code: 'CF-PI-000001');
        $this->inflow('payment_out', 20000, code: 'CF-PO-000001');

        // 500,000 + 70,000 - 120,000 - 30,000 - 20,000
        $this->assertEquals(400000.0, $this->dashboard()['cash_balance']);
    }

    public function test_the_balance_ignores_soft_deleted_rows(): void
    {
        $this->inflow('sale', 500000);
        $expense = $this->inflow('expense', 100000, code: 'CF-E-000001');

        $this->assertEquals(400000.0, $this->dashboard()['cash_balance']);

        $expense->delete();

        $this->assertEquals(500000.0, $this->dashboard()['cash_balance']);
    }

    public function test_a_branch_scoped_user_only_ever_sees_their_own_branch(): void
    {
        $this->inflow('sale', 500000);
        $this->inflow('sale', 250000, branch: $this->otherBranch);

        // The acting user belongs to $this->branch, so the ledger scope already excludes
        // the other branch before any filter is applied.
        $this->assertEquals(500000.0, $this->dashboard()['cash_balance']);

        $this->assertEquals(500000.0, $this->dashboard((string) $this->branch->id)['cash_balance']);

        // Passing another branch id must not widen the scope to reach its money.
        $this->assertEquals(0.0, $this->dashboard((string) $this->otherBranch->id)['cash_balance']);
    }

    public function test_another_businesss_ledger_is_never_counted(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);

        $this->inflow('sale', 500000);

        CashFlow::withoutGlobalScopes()->create([
            'transaction_code' => 'CF-FOREIGN-1',
            'type' => 'sale',
            'amount' => 999999,
            'currency' => 'UGX',
            'business_id' => $otherBusiness->id,
            'business_branch_id' => $otherBranch->id,
            'category' => 'product_sales',
            'payment_method' => 'cash',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertEquals(500000.0, $this->dashboard()['cash_balance']);
    }

    public function test_an_empty_ledger_reports_zero(): void
    {
        $this->assertEquals(0.0, $this->dashboard()['cash_balance']);
    }

    /**
     * The adjustments endpoint is gated on a finance role. 'executive' is used because
     * it is the only name that satisfies both gates at once: RolePermissions lists the
     * branch manager as 'branchmanager', while FinanceController checks
     * 'branch_manager', so no single branch-manager name clears both.
     */
    private function actAsFinanceRole(): User
    {
        $role = Role::factory()->create([
            'business_id' => $this->user->business_id,
            'name' => 'executive',
        ]);

        $manager = User::factory()->create([
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($manager);

        return $manager;
    }

    private function adjustmentPayload(array $overrides = []): array
    {
        return array_merge([
            // No transaction_code: the endpoint generates it, which is why this
            // payload used to fail with a not-null violation.
            'amount' => 25000,
            'currency' => 'UGX',
            'direction' => 'credit',
            'business_branch_id' => $this->branch->id,
            'description' => 'Counting correction',
            'category' => 'rent',
            'payment_method' => 'cash',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ], $overrides);
    }

    /**
     * The direction is what tells the ledger which way the money moved, so each way has
     * to move the balance the right amount.
     */
    public function test_a_credit_adjustment_raises_the_balance_and_a_debit_lowers_it(): void
    {
        $this->inflow('sale', 100000);
        $this->actAsFinanceRole();

        $this->postJson('/api/finances/adjustments', $this->adjustmentPayload([
            'direction' => 'credit',
        ]))->assertOk();

        $this->assertEquals(125000.0, $this->dashboard()['cash_balance']);

        $this->postJson('/api/finances/adjustments', $this->adjustmentPayload([
            'direction' => 'debit',
        ]))->assertOk();

        $this->assertEquals(100000.0, $this->dashboard()['cash_balance'], 'A debit adjustment takes money back out.');
    }

    /**
     * An adjustment moves cash without being revenue or an expense, which is why it is
     * filed under its own type. Money in must not inflate the revenue figures.
     */
    public function test_an_adjustment_moves_cash_without_being_revenue_or_an_expense(): void
    {
        $this->inflow('sale', 100000);
        $this->actAsFinanceRole();

        $this->postJson('/api/finances/adjustments', $this->adjustmentPayload([
            'direction' => 'credit',
            'amount' => 90000,
        ]))->assertOk();

        $dashboard = $this->dashboard();

        $this->assertEquals(190000.0, $dashboard['cash_balance']);
        $this->assertEquals(100000.0, $dashboard['total_revenue'], 'An adjustment is not a sale.');
        $this->assertEquals(0.0, $dashboard['total_expenses'], 'A credit adjustment is not an expense.');
    }

    /**
     * A branch manager is documented as having near-executive powers inside their own
     * branch, so they must be able to post an adjustment. They could not: the gate
     * compared a raw lowercased role name against 'branch_manager', while the role is
     * stored as 'BranchManager' and RolePermissions::roleName() strips separators.
     */
    public function test_a_branch_manager_can_post_an_adjustment(): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->user->business_id,
            'name' => 'BranchManager',
        ]);

        $manager = User::factory()->create([
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($manager);

        $this->postJson('/api/finances/adjustments', $this->adjustmentPayload())
            ->assertOk();

        $this->assertEquals(1, CashFlow::count());
    }

    public function test_an_adjustment_without_a_direction_is_rejected(): void
    {
        $this->actAsFinanceRole();

        $payload = $this->adjustmentPayload();
        unset($payload['direction']);

        $this->postJson('/api/finances/adjustments', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction');

        $this->assertEquals(0.0, $this->dashboard()['cash_balance'], 'A rejected adjustment must not reach the ledger.');
        $this->assertSame(0, CashFlow::count());
    }

    public function test_an_adjustment_with_an_unknown_direction_is_rejected(): void
    {
        $this->actAsFinanceRole();

        $this->postJson('/api/finances/adjustments', $this->adjustmentPayload(['direction' => 'sideways']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction');

        $this->assertSame(0, CashFlow::count());
    }

    /**
     * Guards the write that would reintroduce the original fault: a stored balance that
     * only some code path keeps up to date.
     */
    public function test_the_balance_is_not_stored_on_the_rows(): void
    {
        $this->inflow('sale', 500000);
        $this->inflow('refund', 200000, code: 'CF-SR-000001');

        $this->assertSame(0, CashFlow::whereNotNull('running_balance')->count(), 'No row may carry a stored balance.');

        // And the column cannot be written through mass assignment.
        $cashFlow = CashFlow::first();
        $cashFlow->forceFill(['running_balance' => 999999])->save();
        $cashFlow->update(['running_balance' => 888888]);

        $this->assertEquals(300000.0, $this->dashboard()['cash_balance']);
    }

    /**
     * The ledger reads chronologically, so a row carries the balance as of its own date.
     *
     * This is what a backdated adjustment exposed: ordered by when it was typed, it sat
     * away from the date it carries and its balance disagreed with the day beside it.
     */
    public function test_a_backdated_entry_lands_in_its_own_date_position(): void
    {
        // An adjustment typed today, but dated last month. direction is required, and the
        // row is created through the endpoint so the schema rule is satisfied.
        $this->actAsFinanceRole();
        $this->postJson('/api/finances/adjustments', $this->adjustmentPayload([
            'direction' => 'credit',
            'amount' => 40000,
            'transaction_date' => now()->subMonth()->toDateString(),
        ]))->assertOk();

        $this->inflow('sale', 100000, code: 'CF-TODAY-1');

        $dashboard = $this->dashboard();
        $rows = collect($dashboard['recent_transactions']);

        $this->assertSame(
            'CF-TODAY-1',
            $rows->first()->transaction_code,
            'The newer transaction_date belongs first, whatever order it was typed in.'
        );
        $this->assertTrue(
            str_starts_with($rows->last()->transaction_code, 'CF-ADJ-'),
            'The backdated adjustment belongs last, beside its own date.'
        );

        // Walking back: the backdated adjustment's balance predates today's sale.
        $balances = $rows->mapWithKeys(fn ($row) => [$row->transaction_code => (float) $row->running_balance]);
        $adjustmentCode = $balances->keys()->first(fn ($code) => str_starts_with($code, 'CF-ADJ-'));

        $this->assertEquals(40000.0, $balances[$adjustmentCode]);
        $this->assertEquals(140000.0, $balances['CF-TODAY-1']);
        $this->assertEquals(140000.0, $dashboard['cash_balance'], 'The total is order-independent either way.');
    }

    /**
     * The transaction table renders a per-row balance, so the rows it lists have to
     * carry a balance consistent with the headline figure rather than sitting null.
     */
    public function test_recent_transactions_carry_a_running_balance(): void
    {
        $this->inflow('sale', 500000, code: 'CF-A-000001');
        $this->inflow('refund', 200000, code: 'CF-B-000001');
        $this->inflow('expense', 100000, code: 'CF-C-000001');

        $dashboard = $this->dashboard();

        $balances = collect($dashboard['recent_transactions'])
            ->mapWithKeys(fn ($row) => [$row->transaction_code => $row->running_balance]);

        $this->assertEquals(
            ['CF-A-000001' => 500000.0, 'CF-B-000001' => 300000.0, 'CF-C-000001' => 200000.0],
            $balances->all(),
            'Each row needs the balance after it is applied, oldest first.'
        );

        // recent_transactions is newest first, so the headline balance is the first row.
        $this->assertEquals(
            collect($balances)->first(),
            $dashboard['cash_balance'],
            'The newest row has to agree with the headline balance.'
        );
    }
}
