<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Purchases are recorded at the till and, increasingly, captured offline where the
 * supplier lookup never ran. The store request has always allowed supplier_id to be
 * nullable, but PurchaseService read $validated['supplier_id'] directly — and "nullable"
 * means the key is simply absent when the client omits it, so the read raised an
 * "Undefined array key" error and the whole sale was lost. It now falls back to null.
 *
 * These pin the no-supplier path, including an item that arrived without a selling
 * price (the offline form no longer refuses to send one).
 */
class PurchaseStoreOptionalSupplierTest extends TestCase
{
    use RefreshDatabase;

    private BusinessBranch $branch;

    private Product $product;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $role = Role::factory()->create([
            'business_id' => $business->id,
            'name' => 'Executive',
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $this->product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'cost_price' => 5000,
            'selling_price' => 7500,
            'quantity' => 10,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);
    }

    public function test_a_purchase_can_be_recorded_without_a_supplier(): void
    {
        $this->postJson('/api/purchases/branch-purchases', [
            'business_branch_id' => $this->branch->id,
            'status' => 'pending',
            'payment_status_id' => $this->paymentMethod->id,
            'currency' => 'UGX',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'cost_price' => 5000],
            ],
        ])->assertOk()->assertJsonPath('purchase.supplier_id', null);

        $this->assertDatabaseCount('purchases', 1);
        $this->assertNull(Purchase::first()->supplier_id);
    }

    public function test_an_item_without_a_selling_price_is_accepted(): void
    {
        $this->postJson('/api/purchases/branch-purchases', [
            'business_branch_id' => $this->branch->id,
            'supplier_id' => null,
            'status' => 'pending',
            'payment_status_id' => $this->paymentMethod->id,
            'currency' => 'UGX',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'cost_price' => 4000,
                    'selling_price' => null,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('purchase_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'selling_price' => null,
        ]);
    }
}
