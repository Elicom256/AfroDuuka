<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cross-tenant isolation regression tests.
 *
 * Every tenant-scoped model uses BaseModel's `business` (business_id) and `branch`
 * (business_branch_id) global scopes. If either is ever removed, overridden, or
 * bypassed, these tests will catch the leak.
 *
 * The pattern: user in business A creates a resource; user in business B attempts to
 * read/update/delete it. The correct answer is 404 — the scope filters it out before
 * the policy is even consulted, so the endpoint cannot confirm the resource exists in
 * another tenant.
 *
 * Route and response-shape notes, because both drifted from the original version of
 * this file:
 *   - products live at /api/products (branch-scoped, no business_id column);
 *   - sales live at /api/sales/branch-sales;
 *   - cash flows live at /api/finances (Paginated, so the rows are at data.data);
 *   - list endpoints that use ListLimit return a bare array under a named key
 *     (customers / suppliers / products / sales), not a {data, meta} envelope.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Business $businessA;

    private Business $businessB;

    private BusinessBranch $branchA;

    private BusinessBranch $branchB;

    private Role $roleA;

    private Role $roleB;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessA = Business::factory()->create(['name' => 'Alpha Trading']);
        $this->businessB = Business::factory()->create(['name' => 'Beta Wholesale']);

        $this->branchA = BusinessBranch::factory()->create([
            'business_id' => $this->businessA->id,
            'name' => 'Alpha HQ',
        ]);
        $this->branchB = BusinessBranch::factory()->create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta HQ',
        ]);

        $this->roleA = Role::factory()->create([
            'business_id' => $this->businessA->id,
            'name' => 'Executive',
        ]);
        $this->roleB = Role::factory()->create([
            'business_id' => $this->businessB->id,
            'name' => 'Executive',
        ]);

        $this->userA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'business_branch_id' => $this->branchA->id,
            'role_id' => $this->roleA->id,
        ]);
        $this->userB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'business_branch_id' => $this->branchB->id,
            'role_id' => $this->roleB->id,
        ]);

        Sanctum::actingAs($this->userA);
    }

    private function switchTo(User $user): void
    {
        Sanctum::actingAs($user);
    }

    public function test_customers_are_tenant_scoped(): void
    {
        $customer = Customer::factory()->create([
            'business_id' => $this->businessB->id,
        ]);

        // User A cannot see, update, or delete Business B's customer.
        $this->getJson("/api/dashboard/customers/{$customer->id}")->assertNotFound();
        $this->putJson("/api/dashboard/customers/{$customer->id}", ['remarks' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/dashboard/customers/{$customer->id}")->assertNotFound();

        // List endpoint must not leak foreign customers.
        $this->getJson('/api/dashboard/customers')
            ->assertOk()
            ->assertJsonCount(0, 'customers');
    }

    public function test_suppliers_are_tenant_scoped(): void
    {
        $supplier = Supplier::factory()->create([
            'business_id' => $this->businessB->id,
        ]);

        $this->getJson("/api/dashboard/suppliers/{$supplier->id}")->assertNotFound();
        $this->putJson("/api/dashboard/suppliers/{$supplier->id}", ['remarks' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/dashboard/suppliers/{$supplier->id}")->assertNotFound();

        $this->getJson('/api/dashboard/suppliers')
            ->assertOk()
            ->assertJsonCount(0, 'suppliers');
    }

    public function test_products_are_tenant_scoped(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branchB->id,
            'name' => 'Foreign SKU',
            'sku' => 'EXT-001',
        ]);

        $this->getJson("/api/products/{$product->id}")->assertNotFound();
        $this->putJson("/api/products/{$product->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/products/{$product->id}")->assertNotFound();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }

    public function test_sales_are_tenant_scoped(): void
    {
        // Sales have no factory; the branch scope is keyed on business_branch_id, which
        // is what a foreign tenant's row carries.
        $sale = Sale::create([
            'business_branch_id' => $this->branchB->id,
            'user_id' => $this->userB->id,
            'customer_id' => Customer::factory()->create(['business_id' => $this->businessB->id])->id,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => 'completed',
        ]);

        $this->getJson("/api/sales/branch-sales/{$sale->id}")->assertNotFound();
        $this->deleteJson("/api/sales/branch-sales/{$sale->id}")->assertNotFound();

        $this->getJson('/api/sales/branch-sales')
            ->assertOk()
            ->assertJsonCount(0, 'sales');
    }

    public function test_cash_flows_are_tenant_scoped(): void
    {
        $flow = CashFlow::factory()->create([
            'business_id' => $this->businessB->id,
            'business_branch_id' => $this->branchB->id,
        ]);

        $this->getJson("/api/finances/{$flow->id}")->assertNotFound();

        // finance list is paginated, so the rows sit under data.data.
        $this->getJson('/api/finances')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    public function test_cross_tenant_list_does_not_leak_counts(): void
    {
        // Create 3 customers in Business A, 5 in Business B. The branch is set
        // explicitly because the creating hook would otherwise stamp every row with the
        // acting user's branch (A), and the branch scope would then hide them from B.
        Customer::factory()->count(3)->create([
            'business_id' => $this->businessA->id,
            'business_branch_id' => $this->branchA->id,
        ]);
        Customer::factory()->count(5)->create([
            'business_id' => $this->businessB->id,
            'business_branch_id' => $this->branchB->id,
        ]);

        // User A must see exactly 3, never 8.
        $this->getJson('/api/dashboard/customers')
            ->assertOk()
            ->assertJsonCount(3, 'customers');

        // Switch to User B — they see 5.
        $this->switchTo($this->userB);
        $this->getJson('/api/dashboard/customers')
            ->assertOk()
            ->assertJsonCount(5, 'customers');
    }
}
