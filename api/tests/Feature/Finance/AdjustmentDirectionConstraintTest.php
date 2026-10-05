<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The rule that an adjustment must say which way the money moved is enforced in the
 * schema, not only by the endpoint, so a seeder or a script cannot reintroduce the rows
 * that made the cash balance quietly incomplete.
 */
class AdjustmentDirectionConstraintTest extends TestCase
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

    private function row(array $overrides = []): array
    {
        return array_merge([
            'transaction_code' => 'CF-'.uniqid(),
            'type' => 'adjustment',
            'amount' => 1000,
            'currency' => 'UGX',
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ], $overrides);
    }

    public function test_the_schema_rejects_an_adjustment_without_a_direction(): void
    {
        $this->expectException(QueryException::class);

        // Written the way the old endpoint wrote it: no direction at all.
        CashFlow::create($this->row());
    }

    public function test_an_adjustment_with_a_direction_is_accepted(): void
    {
        foreach (['credit', 'debit'] as $direction) {
            $cashFlow = CashFlow::create($this->row(['direction' => $direction]));

            $this->assertSame($direction, $cashFlow->direction);
        }
    }

    /**
     * A sale, purchase or refund takes its direction from its type, so direction stays
     * nullable for them. The constraint must not get in the way of the whole ledger.
     */
    public function test_other_types_may_still_omit_a_direction(): void
    {
        foreach (['sale', 'purchase', 'expense', 'payment_in', 'payment_out', 'refund'] as $type) {
            $cashFlow = CashFlow::create($this->row(['type' => $type]));

            $this->assertNull($cashFlow->direction);
        }
    }

    public function test_the_constraint_is_created_alongside_the_table_and_is_validated(): void
    {
        // Declared in the create migration rather than added later, so it holds from the
        // first row and there is no window in which an unsigned adjustment can be
        // written. convalidated true means Postgres checked it and found nothing to
        // complain about, which holds because the table is created empty.
        $constraint = DB::selectOne(
            "SELECT convalidated FROM pg_constraint WHERE conname = 'cash_flows_adjustment_requires_direction'"
        );

        $this->assertNotNull($constraint, 'The constraint is missing.');
        $this->assertTrue((bool) $constraint->convalidated, 'It should be a validated constraint, not NOT VALID.');
    }
}
