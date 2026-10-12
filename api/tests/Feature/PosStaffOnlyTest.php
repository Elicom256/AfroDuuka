<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The till is for staff, not for counterparties.
 *
 * The POS routes are declared under auth:sanctum with no role gate, and
 * RequireBusiness only asks whether the caller has a business_id. A customer record is
 * a User row carrying that same business_id — StoreCustomerRequest assigns the
 * 'customer' role and the caller's own business_id — so a customer account satisfies
 * every middleware in the chain and lands in the till.
 *
 * That is not a customer account being mistreated; it is the opposite. A customer is an
 * external party on the business's books. Being able to ring up sales, take payment,
 * decrement stock and write cash-flow rows is staff authority, and holding it because a
 * counterparty happens to have a tenant id is exactly the kind of confusion that makes a
 * multi-tenant boundary hard to reason about.
 */
class PosStaffOnlyTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branch;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        PaymentMethod::create([
            'business_id' => $this->business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        $this->product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 50,
            'cost_price' => 1000,
            'selling_price' => 1000,
            'reorder_level' => 1,
            'status' => 'active',
        ]);
    }

    private function counterparty(string $roleName): User
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => $roleName,
        ]);

        return User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    private function checkoutPayload(): array
    {
        return [
            'business_branch_id' => $this->branch->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
            'payments' => [['method' => 'cash', 'amount' => 1000]],
            'currency' => 'UGX',
        ];
    }

    public function test_a_customer_account_cannot_ring_up_a_sale(): void
    {
        Sanctum::actingAs($this->counterparty('customer'));

        $this->postJson('/api/pos/checkout', $this->checkoutPayload())
            ->assertStatus(403);

        $this->assertSame(0, Sale::count());
        $this->assertSame(50, $this->product->fresh()->quantity);
    }

    public function test_a_supplier_account_cannot_ring_up_a_sale(): void
    {
        Sanctum::actingAs($this->counterparty('supplier'));

        $this->postJson('/api/pos/checkout', $this->checkoutPayload())
            ->assertStatus(403);

        $this->assertSame(0, Sale::count());
    }

    public function test_a_counterparty_cannot_search_the_catalogue(): void
    {
        Sanctum::actingAs($this->counterparty('customer'));

        $this->getJson('/api/pos/products/search?q=widget')->assertStatus(403);
        $this->getJson('/api/pos/customers/search?q=am')->assertStatus(403);
    }

    public function test_staff_can_still_ring_up_a_sale(): void
    {
        // The gate must not close the till to the people who work on it. Operations is
        // the role that sells; BranchManager and Executive must be included too.
        //
        // Each role gets its own product rather than the sale rows being deleted
        // between iterations: a completed POS sale writes a receipt, and receipts
        // reference sales by foreign key, so clearing them is not a one-liner.
        foreach (['Operations', 'BranchManager', 'Executive'] as $roleName) {
            $product = Product::factory()->create([
                'business_branch_id' => $this->branch->id,
                'quantity' => 50,
                'cost_price' => 1000,
                'selling_price' => 1000,
                'reorder_level' => 1,
                'status' => 'active',
            ]);

            Sanctum::actingAs($this->counterparty($roleName));

            $this->postJson('/api/pos/checkout', [
                'business_branch_id' => $this->branch->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000],
                ],
                'payments' => [['method' => 'cash', 'amount' => 1000]],
                'currency' => 'UGX',
            ])->assertStatus(200, "{$roleName} should be able to complete a sale");
        }
    }
}
