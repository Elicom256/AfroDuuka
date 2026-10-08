<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The transactions list and the three finance reports were unreachable.
 *
 * FinanceController::authorizeSensitiveFinance() calls
 * App\Support\Auth\RolePermissions without importing it. Inside the controller's
 * namespace PHP then resolves the name to App\Http\Controllers\RolePermissions, which
 * does not exist, and raises an Error — not an \Exception — so the catch (\Exception)
 * around every one of these methods never saw it and each request answered 500.
 *
 * Nothing caught it because the suite only ever exercised POST /finances/adjustments,
 * which authorizes in StoreCashFlowAdjustmentRequest::authorize() instead.
 *
 * The second failure these pin is the status code. The role gate and the branch scope
 * check both end in abort(), which throws an HttpException — an \Exception — so while
 * they sat inside the try a legitimate 403 and an unknown id were both flattened into
 * 422 "Failed to fetch ...". A caller cannot tell "you may not read this" from "that
 * does not exist" if both arrive as 422.
 */
class FinanceTransactionsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branch;

    private User $executive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $this->executive = $this->userWithRole('executive');
    }

    private function userWithRole(string $roleName): User
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

        $this->actingAs($user);

        return $user;
    }

    private function cashFlow(array $overrides = []): CashFlow
    {
        return CashFlow::create(array_merge([
            'transaction_code' => 'CF-'.uniqid(),
            'type' => 'sale',
            'amount' => 250000,
            'currency' => 'UGX',
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'category' => 'product_sales',
            'payment_method' => 'cash',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->executive->id,
        ], $overrides));
    }

    public function test_the_transactions_list_loads(): void
    {
        $this->cashFlow(['transaction_code' => 'CF-S-000001']);

        $this->getJson('/api/finances/transactions')
            ->assertOk()
            ->assertJsonPath('data.data.0.transaction_code', 'CF-S-000001');
    }

    public function test_the_transactions_list_accepts_its_filters(): void
    {
        $this->cashFlow(['transaction_code' => 'CF-S-000001', 'type' => 'sale']);
        $this->cashFlow(['transaction_code' => 'CF-E-000001', 'type' => 'expense']);

        $this->getJson('/api/finances/transactions?type=expense')
            ->assertOk()
            ->assertJsonPath('data.data.0.transaction_code', 'CF-E-000001')
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/finances/transactions?search=CF-E')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        // The branch filter arrives as a query string while the permitted set holds
        // integers. Asking for your own branch must not be treated as out of scope.
        $this->getJson('/api/finances/transactions?branch_id='.$this->branch->id)
            ->assertOk()
            ->assertJsonCount(2, 'data.data');
    }

    /**
     * The detail page is a routed view of this payload, and it reads every relation by
     * name. A relation silently missing from the eager-load list renders as "None" rather
     * than erroring, so the whole set is asserted explicitly rather than spot-checked.
     *
     * Relation keys come back snake_cased: the method is createdBy() and the key is
     * created_by, which is also the foreign-key column, so the loaded user replaces the
     * raw id. The detail view relies on that.
     */
    public function test_a_single_transaction_loads_with_every_source_relation_resolved(): void
    {
        $customerUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
        ]);

        $customer = Customer::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $customerUser->id,
            'company_name' => 'Afro Wholesale Ltd',
        ]);

        $cashFlow = $this->cashFlow(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/finances/transactions/{$cashFlow->id}")->assertOk();

        foreach ([
            'branch', 'created_by', 'customer', 'supplier', 'sale', 'purchase',
            'sale_return', 'purchase_return', 'stock_transfer', 'expense',
        ] as $relation) {
            $this->assertArrayHasKey($relation, $response->json('data'));
        }

        $this->assertSame($this->branch->name, $response->json('data.branch.name'));

        // created_by arrives as the user, not as the raw foreign key, so the detail view
        // can name whoever recorded the transaction.
        $this->assertSame($this->executive->id, $response->json('data.created_by.id'));

        $this->assertSame('Afro Wholesale Ltd', $response->json('data.customer.company_name'));
    }

    /**
     * A customer's name is not a column: it is either the company_name or the first and
     * last name of the user behind it. The detail view can only render one of those if
     * customer.user came back with it.
     */
    public function test_a_single_transaction_carries_the_users_name_for_a_person_customer(): void
    {
        $customerUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'firstname' => 'Amina',
            'lastname' => 'Nabirye',
        ]);

        $customer = Customer::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $customerUser->id,
            'company_name' => null,
        ]);

        $cashFlow = $this->cashFlow(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/finances/transactions/{$cashFlow->id}")->assertOk();

        $this->assertSame('Amina', $response->json('data.customer.user.firstname'));
        $this->assertSame('Nabirye', $response->json('data.customer.user.lastname'));
    }

    public function test_an_unknown_transaction_is_a_404_not_a_422(): void
    {
        $this->getJson('/api/finances/transactions/999999')->assertNotFound();
    }

    public function test_the_revenue_report_loads(): void
    {
        $this->cashFlow(['type' => 'sale', 'amount' => 400000]);

        $this->getJson('/api/finances/reports/revenue')->assertOk();
    }

    public function test_the_expense_report_loads(): void
    {
        $this->cashFlow(['type' => 'expense', 'amount' => 40000, 'category' => 'rent']);

        $this->getJson('/api/finances/reports/expenses')->assertOk();
    }

    public function test_the_income_summary_loads(): void
    {
        $this->cashFlow(['type' => 'sale', 'amount' => 400000]);

        $this->getJson('/api/finances/reports/income-summary')->assertOk();
    }

    public function test_the_statements_load(): void
    {
        $this->cashFlow();

        $this->getJson('/api/finances/reports/branch-statement/'.$this->branch->id)->assertOk();
        $this->getJson('/api/finances/reports/business-statement')->assertOk();
    }

    /**
     * Operations runs the floor and may post an adjustment, but it may not read the
     * books, so canManageSensitiveFinance() refuses it.
     *
     * The gate used to sit inside the try, so this answered 422 "Failed to fetch
     * transactions" — indistinguishable from a broken query.
     */
    public function test_a_role_that_may_not_read_the_books_gets_a_403_not_a_422(): void
    {
        $this->cashFlow();

        $this->userWithRole('operations');

        foreach ([
            '/api/finances/transactions',
            '/api/finances/transactions/1',
            '/api/finances/reports/revenue',
            '/api/finances/reports/expenses',
            '/api/finances/reports/income-summary',
            '/api/finances/reports/business-statement',
        ] as $endpoint) {
            $this->getJson($endpoint)
                ->assertStatus(403, "{$endpoint} must refuse a role that may not read the books with 403.");
        }
    }

    /**
     * Asking for another branch is a scope question, not a broken request, so it answers
     * 403 the same way rather than 422.
     */
    public function test_reading_another_branchs_transactions_is_refused(): void
    {
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $this->getJson('/api/finances/transactions?branch_id='.$otherBranch->id)->assertStatus(403);
    }
}