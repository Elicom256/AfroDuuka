<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SaleOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;
    protected BusinessBranch $branch;
    protected Role $role;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->role = Role::factory()->create(['business_id' => $this->business->id]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $this->role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    private function product(): Product
    {
        return Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Order Widget',
            'status' => 'active',
        ]);
    }

    private function makeSupplier(): Supplier
    {
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $this->role->id,
        ]);

        return Supplier::create([
            'user_id' => $user->id,
            'supplier_code' => 'SUP-' . $this->faker->unique()->numerify('####'),
            'company_name' => $this->faker->company(),
            'status' => 'active',
        ]);
    }

    private function createSaleOrder(): SaleOrder
    {
        $product = $this->product();

        $response = $this->postJson('/api/sale-orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        return SaleOrder::findOrFail($response->json('data.id'));
    }

    public function test_creates_sale_order_with_items_and_totals(): void
    {
        $product = $this->product();

        $response = $this->postJson('/api/sale-orders', [
            'customer_id' => null,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 5000],
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 3000],
            ],
            'notes' => 'Rush order',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', '13000.00');

        $orderId = $response->json('data.id');

        $this->assertMatchesRegularExpression('/^ORD-\d{6}$/', $response->json('data.order_number'));

        $this->assertDatabaseHas('sale_orders', [
            'id' => $orderId,
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'total_amount' => '13000.00',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('sale_order_items', [
            'sale_order_id' => $orderId,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => '5000.00',
            'subtotal' => '10000.00',
        ]);
    }

    public function test_sale_order_requires_at_least_one_item(): void
    {
        $this->postJson('/api/sale-orders', ['items' => []])
            ->assertStatus(422);
    }

    public function test_sale_order_rejects_dangling_product(): void
    {
        $this->postJson('/api/sale-orders', [
            'items' => [['product_id' => 999999, 'quantity' => 1, 'unit_price' => 100]],
        ])->assertStatus(422);
    }

    public function test_updates_sale_order_status(): void
    {
        $order = $this->createSaleOrder();

        $this->putJson("/api/sale-orders/{$order->id}", ['status' => 'approved'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('sale_orders', [
            'id' => $order->id,
            'status' => 'approved',
        ]);
    }

    public function test_procurement_cannot_approve_a_purchase_order(): void
    {
        $this->role->update(['name' => 'Procurement']);
        $purchaseOrder = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'supplier_id' => null,
            'order_number' => 'PO-SECURITY-001',
            'total_amount' => 100,
            'status' => 'pending',
        ]);

        $this->putJson("/api/purchase-orders/{$purchaseOrder->id}", [
            'status' => 'approved',
        ])->assertStatus(403);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'pending',
        ]);
    }

    public function test_procurement_cannot_use_dedicated_purchase_order_approval_endpoint(): void
    {
        $this->role->update(['name' => 'Procurement']);
        $purchaseOrder = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'supplier_id' => null,
            'order_number' => 'PO-SECURITY-002',
            'total_amount' => 100,
            'status' => 'pending',
        ]);

        $this->postJson("/api/procurement/purchase-orders/{$purchaseOrder->id}/approve")
            ->assertStatus(403);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => 'pending',
        ]);
    }

    public function test_sale_order_rejects_unknown_status(): void
    {
        $order = $this->createSaleOrder();

        $this->putJson("/api/sale-orders/{$order->id}", ['status' => 'delivered'])
            ->assertStatus(422);
    }

    public function test_creates_purchase_order_with_supplier_and_items(): void
    {
        $this->role->update(['name' => 'Procurement']);
        $supplier = $this->makeSupplier();
        $product = $this->product();

        $response = $this->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 600],
            ],
            'notes' => 'Restock',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', '6000.00');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $response->json('data.id'),
            'supplier_id' => $supplier->id,
            'total_amount' => '6000.00',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $response->json('data.id'),
            'product_id' => $product->id,
            'quantity' => 10,
            'subtotal' => '6000.00',
        ]);
    }

    public function test_operations_cannot_create_purchase_orders_through_either_endpoint(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->product();
        $payload = [
            'supplier_id' => $supplier->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ]],
        ];

        $this->postJson('/api/purchase-orders', $payload)->assertStatus(403);
        $this->postJson('/api/procurement/purchase-orders', $payload)->assertStatus(403);

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_operations_cannot_edit_purchase_order_notes(): void
    {
        $purchaseOrder = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'supplier_id' => null,
            'order_number' => 'PO-SECURITY-003',
            'total_amount' => 100,
            'status' => 'pending',
            'notes' => 'Original notes',
        ]);

        $this->putJson("/api/purchase-orders/{$purchaseOrder->id}", [
            'notes' => 'Unauthorized edit',
        ])->assertStatus(403);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'notes' => 'Original notes',
        ]);
    }

    public function test_purchase_order_requires_supplier(): void
    {
        $product = $this->product();

        $this->postJson('/api/purchase-orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ])->assertStatus(422);
    }

    public function test_updates_purchase_order_status(): void
    {
        $this->role->update(['name' => 'Procurement']);
        $supplier = $this->makeSupplier();
        $product = $this->product();

        $create = $this->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ])->assertStatus(201);

        $this->putJson('/api/purchase-orders/' . $create->json('data.id'), ['status' => 'cancelled'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_user_cannot_access_another_business_sale_order(): void
    {
        $order = $this->createSaleOrder();

        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);
        $otherRole = Role::factory()->create(['business_id' => $otherBusiness->id]);
        $otherUser = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'business_branch_id' => $otherBranch->id,
            'role_id' => $otherRole->id,
        ]);

        Sanctum::actingAs($otherUser);

        $this->getJson("/api/sale-orders/{$order->id}")
            ->assertStatus(404);
    }
}