<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseReceiveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessBranch $branch;

    private Product $product;

    private Purchase $purchase;

    private PurchaseItem $purchaseItem;

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

        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'cost_price' => 5000,
            'selling_price' => 7500,
            'quantity' => 10,
        ]);

        $this->purchase = Purchase::create([
            'business_branch_id' => $this->branch->id,
            'status' => 'pending',
            'total_amount' => 50000,
        ]);

        $this->purchaseItem = PurchaseItem::create([
            'purchase_id' => $this->purchase->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'cost_price' => 10000,
            'selling_price' => 15000,
            'subtotal' => 50000,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);
    }

    public function test_receiving_purchase_keeps_stock_stable_when_recorded(): void
    {
        $response = $this->postJson("/api/purchases/branch-purchases/{$this->purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $this->purchaseItem->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertEquals(10, $this->product->fresh()->quantity);
    }

    public function test_receiving_purchase_updates_cost_price(): void
    {
        $this->postJson("/api/purchases/branch-purchases/{$this->purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $this->purchaseItem->id, 'quantity' => 5],
            ],
        ]);

        $this->assertEquals(10000, $this->product->fresh()->cost_price);
    }

    public function test_receiving_purchase_updates_selling_price(): void
    {
        $this->postJson("/api/purchases/branch-purchases/{$this->purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $this->purchaseItem->id, 'quantity' => 5],
            ],
        ]);

        $this->assertEquals(15000, $this->product->fresh()->selling_price);
    }

    public function test_receiving_purchase_sets_status_to_completed(): void
    {
        $this->postJson("/api/purchases/branch-purchases/{$this->purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $this->purchaseItem->id, 'quantity' => 5],
            ],
        ]);

        $this->assertEquals('completed', $this->purchase->fresh()->status);
        $this->assertNotNull($this->purchase->fresh()->received_at);
    }

    public function test_double_receive_is_blocked(): void
    {
        $this->postJson("/api/purchases/branch-purchases/{$this->purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $this->purchaseItem->id, 'quantity' => 5],
            ],
        ])->assertStatus(200);

        $this->postJson("/api/purchases/branch-purchases/{$this->purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $this->purchaseItem->id, 'quantity' => 5],
            ],
        ])->assertStatus(422);
    }

    public function test_rejects_quantity_above_ordered(): void
    {
        $response = $this->postJson("/api/purchases/branch-purchases/{$this->purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $this->purchaseItem->id, 'quantity' => 999],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals(10, $this->product->fresh()->quantity);
    }

    public function test_purchase_can_be_created_with_selling_price(): void
    {
        $response = $this->postJson('/api/purchases/branch-purchases', [
            'supplier_id' => null,
            'business_branch_id' => $this->branch->id,
            'status' => 'pending',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'cost_price' => 8000,
                    'selling_price' => 12000,
                ],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_purchase_item_selling_price_is_validated(): void
    {
        $response = $this->postJson('/api/purchases/branch-purchases', [
            'supplier_id' => null,
            'business_branch_id' => $this->branch->id,
            'status' => 'pending',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'cost_price' => 8000,
                    'selling_price' => -500,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_creating_completed_purchase_does_not_inflate_stock_beyond_available_inventory_and_updates_prices(): void
    {
        $this->product->update(['quantity' => 10]);

        $response = $this->postJson('/api/purchases/branch-purchases', [
            'supplier_id' => null,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                    'cost_price' => 8000,
                    'selling_price' => 12000,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('purchase.status', 'completed');

        $product = Product::find($this->product->id);
        $this->assertSame(10, (int) $product->quantity);
        $this->assertEquals(8000, (float) $product->cost_price);
        $this->assertEquals(12000, (float) $product->selling_price);

        $this->assertDatabaseHas('purchases', [
            'id' => $response->json('purchase.id'),
            'status' => 'completed',
        ]);
    }

    public function test_creating_purchase_without_status_defaults_to_completed_and_does_not_inflate_stock(): void
    {
        $response = $this->postJson('/api/purchases/branch-purchases', [
            'supplier_id' => null,
            'business_branch_id' => $this->branch->id,
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'cost_price' => 6000,
                    'selling_price' => 9000,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('purchase.status', 'completed');

        $product = Product::find($this->product->id);
        $this->assertSame(10, (int) $product->quantity);
        $this->assertEquals(6000, (float) $product->cost_price);
        $this->assertEquals(9000, (float) $product->selling_price);
    }

    public function test_creating_completed_purchase_without_selling_price_keeps_existing_price(): void
    {
        $this->product->update(['selling_price' => 20000]);

        $response = $this->postJson('/api/purchases/branch-purchases', [
            'supplier_id' => null,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'cost_price' => 6500,
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertSame(10, (int) Product::find($this->product->id)->quantity);
        $this->assertEquals(6500, (float) Product::find($this->product->id)->cost_price);
        $this->assertEquals(20000, (float) Product::find($this->product->id)->selling_price);
    }

    public function test_creating_pending_purchase_does_not_update_product_until_received(): void
    {
        $response = $this->postJson('/api/purchases/branch-purchases', [
            'supplier_id' => null,
            'business_branch_id' => $this->branch->id,
            'status' => 'pending',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 4,
                    'cost_price' => 7000,
                    'selling_price' => 11000,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('purchase.status', 'pending');

        $product = Product::find($this->product->id);
        $this->assertSame(10, (int) $product->quantity);
        $this->assertEquals(5000, (float) $product->cost_price);
        $this->assertEquals(7500, (float) $product->selling_price);
    }
}
