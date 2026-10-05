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
 * Adjustments recorded before a direction became required cannot move the cash
 * balance, because nothing records which way the money went. These tests pin that they
 * are reported as a gap and can be corrected, rather than being silently guessed at.
 */
class UnsignedAdjustmentRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $business->id])->id,
        ]);

        $this->actingAs($this->user);
    }

    private function legacyAdjustment(string $code, float $amount = 10000): CashFlow
    {
        // Written the way the old endpoint did: type forced to adjustment, no direction.
        return CashFlow::create([
            'transaction_code' => $code,
            'type' => 'adjustment',
            'amount' => $amount,
            'currency' => 'UGX',
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'description' => 'Legacy correction',
            'status' => 'completed',
            'transaction_date' => now()->subMonth()->toDateString(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_an_unsigned_adjustment_is_excluded_from_the_balance_and_counted(): void
    {
        CashFlow::create([
            'transaction_code' => 'CF-SALE-1',
            'type' => 'sale',
            'amount' => 100000,
            'currency' => 'UGX',
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $this->legacyAdjustment('CF-ADJ-LEGACY-1');

        $dashboard = app(FinanceService::class)->dashboard();

        $this->assertEquals(100000.0, $dashboard['cash_balance'], 'An unsigned adjustment must not be guessed at.');
        $this->assertEquals(1, $dashboard['unsigned_adjustments'], 'The gap has to be reported, not hidden.');
    }

    public function test_a_signed_adjustment_is_not_counted_as_unsigned(): void
    {
        $this->legacyAdjustment('CF-ADJ-LEGACY-2')->forceFill(['direction' => 'debit'])->save();

        $dashboard = app(FinanceService::class)->dashboard();

        $this->assertEquals(0, $dashboard['unsigned_adjustments']);
        $this->assertEquals(-10000.0, $dashboard['cash_balance']);
    }

    public function test_the_command_lists_unsigned_adjustments(): void
    {
        $this->legacyAdjustment('CF-ADJ-LEGACY-3', 4200);

        $this->artisan('duukaflow:finance:unsigned-adjustments')
            ->expectsOutputToContain('1 adjustment(s) carry no direction')
            ->expectsOutputToContain('CF-ADJ-LEGACY-3')
            ->assertSuccessful();
    }

    public function test_the_command_reports_a_clean_ledger(): void
    {
        $this->artisan('duukaflow:finance:unsigned-adjustments')
            ->expectsOutputToContain('No unsigned adjustments')
            ->assertSuccessful();
    }

    public function test_the_command_records_a_direction_and_the_balance_follows(): void
    {
        $this->legacyAdjustment('CF-ADJ-LEGACY-4', 15000);

        $this->artisan('duukaflow:finance:unsigned-adjustments', [
            '--code' => 'CF-ADJ-LEGACY-4',
            '--direction' => 'credit',
        ])->assertSuccessful();

        $this->assertSame('credit', CashFlow::where('transaction_code', 'CF-ADJ-LEGACY-4')->firstOrFail()->direction);
        $this->assertEquals(15000.0, app(FinanceService::class)->dashboard()['cash_balance']);
        $this->assertEquals(0, app(FinanceService::class)->dashboard()['unsigned_adjustments']);
    }

    public function test_the_command_refuses_an_unknown_direction(): void
    {
        $this->legacyAdjustment('CF-ADJ-LEGACY-5');

        $this->artisan('duukaflow:finance:unsigned-adjustments', [
            '--code' => 'CF-ADJ-LEGACY-5',
            '--direction' => 'sideways',
        ])->assertFailed();

        $this->assertNull(CashFlow::where('transaction_code', 'CF-ADJ-LEGACY-5')->firstOrFail()->direction);
    }

    public function test_the_command_refuses_to_reclassify_a_non_adjustment(): void
    {
        $sale = CashFlow::create([
            'transaction_code' => 'CF-SALE-9',
            'type' => 'sale',
            'amount' => 500,
            'currency' => 'UGX',
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $this->artisan('duukaflow:finance:unsigned-adjustments', [
            '--code' => 'CF-SALE-9',
            '--direction' => 'credit',
        ])->assertFailed();

        $this->assertNull($sale->refresh()->direction, 'A sale takes its direction from its type.');
    }

    public function test_the_command_reports_a_missing_code(): void
    {
        $this->artisan('duukaflow:finance:unsigned-adjustments', [
            '--code' => 'NOPE',
            '--direction' => 'credit',
        ])->assertFailed();
    }
}
