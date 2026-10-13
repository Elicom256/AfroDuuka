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
 * Every tenant-scoped model uses BaseModel's `business` global scope, which
 * rewrites every query with `where business_id = ?`. If the scope is ever
 * removed, overridden, or bypassed, these tests will catch the leak.
 *
 * The pattern: user in business A creates a resource; user in business B
 * attempts to read/update/delete it. The correct answer is 404 — the scope
 * filters it out before the policy is even consulted, so the endpoint cannot
 * confirm the resource exists in another tenant.
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
            'name' => 'Foreign Client',
        ]);

        // User A cannot see, update, or delete Business B's customer.
        $this->getJson("/api/dashboard/customers/{$customer->id}")->assertNotFound();
        $this->putJson("/api/dashboard/customers/{$customer->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/dashboard/customers/{$customer->id}")->assertNotFound();

        // List endpoint must not leak foreign customers.
        $this->getJson('/api/dashboard/customers')->assertJsonCount(0, 'data');

        // User A creates their own customer — it works.
        $this->postJson('/api/dashboard/customers', ['name' => 'Local Client'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Local Client');
    }

    public function test_suppliers_are_tenant_scoped(): void
    {
        $supplier = Supplier::factory()->create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Vendor',
        ]);

        $this->getJson("/api/dashboard/suppliers/{$supplier->id}")->assertNotFound();
        $this->putJson("/api/dashboard/suppliers/{$supplier->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/dashboard/suppliers/{$supplier->id}")->assertNotFound();

        $this->getJson('/api/dashboard/suppliers')->assertJsonCount(0, 'data');

        $this->postJson('/api/dashboard/suppliers', ['name' => 'Local Vendor'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Local Vendor');
    }

    public function test_products_are_tenant_scoped(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branchB->id,
            'name' => 'Foreign SKU',
            'sku' => 'EXT-001',
        ]);

        $this->getJson("/api/dashboard/products/{$product->id}")->assertNotFound();
        $this->putJson("/api/dashboard/products/{$product->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/dashboard/products/{$product->id}")->assertNotFound();

        $this->getJson('/api/dashboard/products')->assertJsonCount(0, 'data');

        $this->postJson('/api/dashboard/products', [
            'name' => 'Local SKU',
            'sku' => 'LOC-001',
            'business_branch_id' => $this->branchA->id,
        ])->assertCreated();
    }

    public function test_sales_are_tenant_scoped(): void
    {
        // A sale needs a customer and items; we just assert the scope on the resource.
        $sale = Sale::factory()->create([
            'business_id' => $this->businessB->id,
            'business_branch_id' => $this->branchB->id,
            'customer_id' => Customer::factory()->create(['business_id' => $this->businessB->id])->id,
            'total_amount' => 1000,
            'status' => 'completed',
        ]);

        $this->getJson("/api/dashboard/sales/{$sale->id}")->assertNotFound();
        $this->deleteJson("/api/dashboard/sales/{$sale->id}")->assertNotFound();

        $this->getJson('/api/dashboard/sales')->assertJsonCount(0, 'data');
    }

    public function test_cash_flows_are_tenant_scoped(): void
    {
        $flow = CashFlow::factory()->create([
            'business_id' => $this->businessB->id,
            'business_branch_id' => $this->branchB->id,
            'amount' => 50000,
            'status' => 'completed',
            'type' => 'sale',
            'category' => 'product_sales',
        ]);

        $this->getJson("/api/finances/cash-flows/{$flow->id}")->assertNotFound();

        $this->getJson('/api/finances/cash-flows')->assertJsonCount(0, 'data');
    }

    public function test_cross_tenant_list_does_not_leak_counts(): void
    {
        // Create 3 customers in Business A, 5 in Business B.
        Customer::factory()->count(3)->create(['business_id' => $this->businessA->id]);
        Customer::factory()->count(5)->create(['business_id' => $this->businessB->id]);

        // User A must see exactly 3, never 8.
        $this->getJson('/api/dashboard/customers')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        // Switch to User B — they see 5.
        $this->switchTo($this->userB);
        $this->getJson('/api/dashboard/customers')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_search_and_filters_do_not_leak_across_tenants(): void
    {
        Customer::factory()->create([
            'business_id' => $this->businessA->id,
            'name' => 'Alpha Client',
        ]);
        Customer::factory()->create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Client',
        ]);

        // Search by Business B's name — User A should get 0 results.
        $this->getJson('/api/dashboard/customers?search=Beta')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Search by Business A's name — User A gets 1.
        $this->getJson('/api/dashboard/customers?search=Alpha')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}