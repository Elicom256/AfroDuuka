<?php

namespace Tests\Feature\Finance;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Role;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Every write path to the ledger has to agree on one rule: an adjustment must say which
 * way the money moved.
 *
 * The rule used to live in exactly one place — the adjustment endpoint's request — with a
 * database CHECK as the only backstop. That left two holes. The generic cash-flow requests
 * accepted `type: 'adjustment'` with no direction, which is precisely the payload the CHECK
 * rejects, so routing them would have answered 500 instead of 422. And a row that predates
 * the requirement could only be repaired from a shell, while the dashboard told ordinary
 * users an administrator had to go and fix it.
 *
 * These cover the application layer of both: the bad payload is now a clean 422 naming the
 * field, and the repair is something a user with the finance role can do over HTTP.
 */
class CashFlowDirectionInvariantTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $this->user = $this->actingAsRole('Executive');
    }

    private function actingAsRole(string $roleName): User
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => $roleName,
        ]);

        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => CashFlowType::Adjustment->value,
            'amount' => 25000,
            'currency' => 'UGX',
            'direction' => CashFlowDirection::Credit->value,
            'business_branch_id' => $this->branch->id,
            'description' => 'Counting correction',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
        ], $overrides);
    }

    private function dashboard(): array
    {
        return app(FinanceService::class)->dashboard();
    }

    /**
     * Recreate a row the CHECK now forbids.
     *
     * Adjustments written before a direction became required are exactly what the repair
     * path is for, and they cannot be written through any model while the constraint
     * stands. Postgres rolls DDL back with the transaction, so the constraint returns when
     * the test ends and no other file sees a weakened schema.
     */
    private function legacyAdjustment(string $code = 'CF-ADJ-LEGACY', float $amount = 15000): CashFlow
    {
        DB::statement('ALTER TABLE cash_flows DROP CONSTRAINT IF EXISTS cash_flows_adjustment_requires_direction');

        return CashFlow::create([
            'transaction_code' => $code,
            'type' => CashFlowType::Adjustment->value,
            'amount' => $amount,
            'currency' => 'UGX',
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'description' => 'Legacy correction',
            'status' => 'completed',
            'transaction_date' => now()->subMonth()->toDateString(),
            'created_by' => $this->user->id,
        ]);
    }

    // ------------------------------------------------- store: the 422 that was a 500

    /**
     * The finding this file exists for.
     *
     * StoreCashFlowRequest accepted `type: 'adjustment'` with no `direction` rule, so the
     * database CHECK raised a QueryException and the caller got a 500. `store` was
     * unrouted, which is the only reason this was ever harmless.
     */
    public function test_an_adjustment_without_a_direction_is_rejected_with_a_422(): void
    {
        $payload = $this->payload();
        unset($payload['direction']);

        $this->postJson('/api/finances', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction');

        $this->assertDatabaseCount('cash_flows', 0);
    }

    public function test_an_adjustment_with_an_unknown_direction_is_rejected(): void
    {
        $this->postJson('/api/finances', $this->payload(['direction' => 'sideways']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction');

        $this->assertDatabaseCount('cash_flows', 0);
    }

    public function test_a_signed_adjustment_is_accepted_and_moves_the_balance(): void
    {
        $this->postJson('/api/finances', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.direction', CashFlowDirection::Credit->value);

        $this->assertEquals(25000.0, $this->dashboard()['cash_balance']);
        $this->assertEquals(0, $this->dashboard()['unsigned_adjustments']);
    }

    public function test_a_debit_adjustment_lowers_the_balance(): void
    {
        $this->postJson('/api/finances', $this->payload([
            'direction' => CashFlowDirection::Debit->value,
            'amount' => 4000,
        ]))->assertOk();

        $this->assertEquals(-4000.0, $this->dashboard()['cash_balance']);
    }

    /**
     * The transaction code identifies the row on every report and exported statement, so a
     * client that chooses it can collide with or squat on one. The request always
     * generates it, which means a client-supplied value is ignored rather than honoured.
     */
    public function test_the_transaction_code_is_generated_server_side(): void
    {
        $this->postJson('/api/finances', $this->payload(['transaction_code' => 'CF-FORGED']))->assertOk();

        $this->assertDatabaseMissing('cash_flows', ['transaction_code' => 'CF-FORGED']);

        $stored = CashFlow::firstOrFail();

        $this->assertStringStartsWith('CF-ADJ-', $stored->transaction_code);
    }

    /**
     * A generic ledger write that accepts `type` from the client is a revenue forgery
     * endpoint: FinanceService::dashboard() totals gross_revenue straight off that column,
     * so an invented `sale` row reports as real money in. Only the manual adjustment is
     * writable by hand; the other six types are written by CashFlowService when the sale,
     * purchase or return that caused them happens.
     */
    public function test_only_a_manual_adjustment_may_be_written_directly(): void
    {
        foreach ([CashFlowType::Sale, CashFlowType::Purchase, CashFlowType::Expense, CashFlowType::Refund] as $type) {
            $this->postJson('/api/finances', $this->payload([
                'type' => $type->value,
                'direction' => null,
            ]))->assertStatus(422)->assertJsonValidationErrors('type');
        }

        $this->assertDatabaseCount('cash_flows', 0);
        $this->assertEquals(0.0, $this->dashboard()['total_revenue']);
    }

    public function test_a_hand_written_row_cannot_be_linked_to_a_sale_or_purchase(): void
    {
        $this->postJson('/api/finances', $this->payload(['sale_id' => 9999]))
            ->assertStatus(422);

        $this->assertDatabaseCount('cash_flows', 0);
    }

    // ------------------------------------------------------------- update: the same rule

    public function test_an_update_cannot_change_the_type(): void
    {
        $cashFlow = $this->legacyAdjustment();

        $this->patchJson("/api/finances/{$cashFlow->id}", ['type' => CashFlowType::Sale->value])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->assertSame(CashFlowType::Adjustment->value, $cashFlow->refresh()->type);
    }

    /**
     * A signed row must stay signed.
     *
     * Sending an explicit null is the one payload that opens a hole on an existing row, and
     * it must be refused with a named field rather than left to the database CHECK, which
     * would answer with a 500.
     */
    public function test_an_update_cannot_clear_the_direction_of_a_signed_adjustment(): void
    {
        $cashFlow = $this->legacyAdjustment();
        $cashFlow->forceFill(['direction' => CashFlowDirection::Credit->value])->save();

        $this->patchJson("/api/finances/{$cashFlow->id}", ['direction' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction');

        $this->assertSame(CashFlowDirection::Credit->value, $cashFlow->refresh()->direction);
        $this->assertEquals(15000.0, $this->dashboard()['cash_balance']);
    }

    /**
     * An edit that says nothing about direction leaves it alone, on a signed row and on an
     * unsigned one.
     *
     * The unsigned row is the interesting case. Refusing every edit until the direction is
     * repaired would freeze a row nobody can describe or correct, and the repair endpoint
     * is where that is fixed — a hole with a stale label is more workable than a hole the
     * API will not let you mention.
     */
    public function test_an_edit_that_omits_the_direction_leaves_it_untouched(): void
    {
        $signed = $this->legacyAdjustment('CF-ADJ-SIGNED');
        $signed->forceFill(['direction' => CashFlowDirection::Debit->value])->save();

        $this->patchJson("/api/finances/{$signed->id}", ['description' => 'Late note'])
            ->assertOk();

        $this->assertSame(CashFlowDirection::Debit->value, $signed->refresh()->direction);

        $unsigned = $this->legacyAdjustment('CF-ADJ-UNSIGNED');

        $this->patchJson("/api/finances/{$unsigned->id}", ['description' => 'Still being investigated'])
            ->assertOk();

        $this->assertNull($unsigned->refresh()->direction);
        $this->assertSame('Still being investigated', $unsigned->refresh()->description);
    }

    public function test_an_update_cannot_set_a_direction_on_a_derived_type(): void
    {
        $sale = CashFlow::create([
            'transaction_code' => 'CF-SALE-UPDATE',
            'type' => CashFlowType::Sale->value,
            'amount' => 90000,
            'currency' => 'UGX',
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // A sale takes its sign from its type. A direction here would be stored, look
        // authoritative, and be ignored by cashEffect() — so it is refused outright.
        $this->patchJson("/api/finances/{$sale->id}", ['direction' => CashFlowDirection::Debit->value])
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction');

        $this->assertNull($sale->refresh()->direction);
        $this->assertEquals(90000.0, $this->dashboard()['cash_balance'], 'The sale keeps its own sign.');
    }

    public function test_an_update_renames_an_adjustment(): void
    {
        $cashFlow = $this->legacyAdjustment();

        $this->patchJson("/api/finances/{$cashFlow->id}", [
            'description' => 'Reclassified after the stock count',
            'amount' => 16000,
        ])->assertOk()->assertJsonPath('data.description', 'Reclassified after the stock count');

        $this->assertEquals(16000.0, (float) $cashFlow->refresh()->amount);
    }

    // ------------------------------------------------- repairing a legacy row over HTTP

    /**
     * The second finding. The dashboard warns that unsigned adjustments are excluded from
     * the cash balance and that somebody must mark each one; until now the only way to do
     * that was a shell and an artisan command.
     */
    public function test_the_endpoint_records_a_direction_and_the_balance_follows(): void
    {
        $cashFlow = $this->legacyAdjustment('CF-ADJ-LEGACY-A', 15000);

        $this->assertEquals(1, $this->dashboard()['unsigned_adjustments']);
        $this->assertEquals(0.0, $this->dashboard()['cash_balance']);

        $this->patchJson("/api/finances/adjustments/{$cashFlow->id}/direction", [
            'direction' => CashFlowDirection::Credit->value,
        ])->assertOk();

        $this->assertSame(CashFlowDirection::Credit->value, $cashFlow->refresh()->direction);
        $this->assertEquals(15000.0, $this->dashboard()['cash_balance']);
        $this->assertEquals(0, $this->dashboard()['unsigned_adjustments'], 'The warning clears once the row counts.');
    }

    public function test_the_endpoint_can_record_a_debit(): void
    {
        $cashFlow = $this->legacyAdjustment('CF-ADJ-LEGACY-B', 8000);

        $this->patchJson("/api/finances/adjustments/{$cashFlow->id}/direction", [
            'direction' => CashFlowDirection::Debit->value,
        ])->assertOk();

        $this->assertEquals(-8000.0, $this->dashboard()['cash_balance']);
    }

    public function test_the_endpoint_rejects_an_unknown_direction(): void
    {
        $cashFlow = $this->legacyAdjustment('CF-ADJ-LEGACY-C');

        $this->patchJson("/api/finances/adjustments/{$cashFlow->id}/direction", [
            'direction' => 'sideways',
        ])->assertStatus(422)->assertJsonValidationErrors('direction');

        $this->assertNull($cashFlow->refresh()->direction);
    }

    public function test_the_endpoint_refuses_to_reclassify_a_derived_type(): void
    {
        $sale = CashFlow::create([
            'transaction_code' => 'CF-SALE-REFUSE',
            'type' => CashFlowType::Sale->value,
            'amount' => 500,
            'currency' => 'UGX',
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $this->patchJson("/api/finances/adjustments/{$sale->id}/direction", [
            'direction' => CashFlowDirection::Credit->value,
        ])->assertStatus(422);

        $this->assertNull($sale->refresh()->direction, 'A sale takes its direction from its type.');
    }

    public function test_the_endpoint_leaves_an_already_signed_row_alone(): void
    {
        $cashFlow = $this->legacyAdjustment('CF-ADJ-LEGACY-D');
        $cashFlow->forceFill(['direction' => CashFlowDirection::Credit->value])->save();

        $this->patchJson("/api/finances/adjustments/{$cashFlow->id}/direction", [
            'direction' => CashFlowDirection::Debit->value,
        ])->assertStatus(422);

        $this->assertSame(CashFlowDirection::Credit->value, $cashFlow->refresh()->direction, 'A signed row is not re-signed.');
    }

    /**
     * Route model binding resolves through the tenant scope on BaseModel, so another
     * business's row is a 404 before the action runs. Cross-tenant repair stays with the
     * artisan command, which is the only writer that drops the scope.
     */
    public function test_the_endpoint_cannot_reach_another_tenants_adjustment(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);

        DB::statement('ALTER TABLE cash_flows DROP CONSTRAINT IF EXISTS cash_flows_adjustment_requires_direction');

        $foreign = CashFlow::withoutGlobalScopes()->create([
            'transaction_code' => 'CF-ADJ-OTHER-TENANT',
            'type' => CashFlowType::Adjustment->value,
            'amount' => 1000,
            'currency' => 'UGX',
            'business_id' => $otherBusiness->id,
            'business_branch_id' => $otherBranch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $this->patchJson("/api/finances/adjustments/{$foreign->id}/direction", [
            'direction' => CashFlowDirection::Credit->value,
        ])->assertNotFound();

        $this->assertNull($foreign->refresh()->direction);
    }

    // -------------------------------------------------------------- who may do any of it

    public function test_operations_cannot_write_the_ledger_directly(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/finances', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('cash_flows', 0);
    }

    public function test_operations_cannot_repair_an_unsigned_adjustment(): void
    {
        $cashFlow = $this->legacyAdjustment('CF-ADJ-LEGACY-OPS');

        $this->actingAsRole('Operations');

        $this->patchJson("/api/finances/adjustments/{$cashFlow->id}/direction", [
            'direction' => CashFlowDirection::Credit->value,
        ])->assertForbidden();

        $this->assertNull($cashFlow->refresh()->direction);
    }

    /**
     * A refusal must not be reported as a payload problem. When the gates lived in the
     * controller, an Operations user's bad request came back 422 "validation failed" and
     * the refusal was invisible behind it.
     */
    public function test_a_refused_role_is_forbidden_even_with_an_invalid_payload(): void
    {
        $cashFlow = $this->legacyAdjustment('CF-ADJ-LEGACY-403');

        $this->actingAsRole('Operations');

        $this->patchJson("/api/finances/adjustments/{$cashFlow->id}/direction", [
            'direction' => 'sideways',
        ])->assertForbidden();

        $this->assertNull($cashFlow->refresh()->direction);
    }

    public function test_a_branch_manager_may_repair_within_their_own_branch(): void
    {
        $cashFlow = $this->legacyAdjustment('CF-ADJ-LEGACY-BM');

        $this->actingAsRole('BranchManager');

        $this->patchJson("/api/finances/adjustments/{$cashFlow->id}/direction", [
            'direction' => CashFlowDirection::Credit->value,
        ])->assertOk();

        $this->assertSame(CashFlowDirection::Credit->value, $cashFlow->refresh()->direction);
    }

    // ---------------------------------------------------------------- the value list

    /**
     * The legal values lived as literals in six places, which is how an adjustment came to
     * be accepted with no direction in the first place. They now come from the enums, and
     * the SQL CASE in FinanceService is built from the same cases as cashEffect().
     */
    public function test_the_two_readings_of_the_sign_mapping_agree(): void
    {
        $adjustment = $this->legacyAdjustment('CF-ADJ-AGREE');
        $adjustment->forceFill(['direction' => CashFlowDirection::Credit->value])->save();

        $sale = CashFlow::create([
            'transaction_code' => 'CF-SALE-AGREE',
            'type' => CashFlowType::Sale->value,
            'amount' => 30000,
            'currency' => 'UGX',
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $dashboard = $this->dashboard();

        // The per-row running balance is cashEffect() in PHP; the headline figure is the
        // CASE in SQL. They are written separately and have to agree.
        $newest = collect($dashboard['recent_transactions'])
            ->sortBy([['transaction_date', 'desc'], ['id', 'desc']])
            ->first();

        $this->assertEquals(
            $dashboard['cash_balance'],
            (float) $newest->running_balance,
            'cashEffect() and the SQL CASE disagree about which way the money moved.'
        );

        $this->assertEquals(45000.0, $dashboard['cash_balance']);
    }

    public function test_every_type_except_adjustment_takes_its_sign_from_the_type(): void
    {
        foreach ([CashFlowType::Sale, CashFlowType::PaymentIn] as $type) {
            $this->assertSame(1, $type->sign(), "{$type->value} moves money in.");
        }

        foreach ([CashFlowType::Purchase, CashFlowType::Expense, CashFlowType::PaymentOut, CashFlowType::Refund] as $type) {
            $this->assertSame(-1, $type->sign(), "{$type->value} moves money out.");
        }

        $this->assertNull(
            CashFlowType::Adjustment->sign(),
            'An adjustment has no type to infer a sign from, which is the whole reason '
            .'direction is required for it.'
        );
    }
}
