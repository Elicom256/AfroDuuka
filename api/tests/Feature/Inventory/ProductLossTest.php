<?php

namespace Tests\Feature\Inventory;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\ProductLoss;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLossTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessBranch $branch;

    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id]);
        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->inventoryService = app(InventoryService::class);

        $this->actingAs($this->user);
    }

    /**
     * RoleFactory's default name is 'Operations', so $this->user is an Operations
     * account by accident. That is correct for the tests below that call
     * InventoryService::writeOff() directly — the service is deliberately ungated so
     * the stock-count and expiry-sweep paths Operations really does drive keep
     * working. The HTTP endpoint is the opposite: it is the one place a loss can be
     * declared, and it now asks the role first.
     */
    private function actingAsRole(string $roleName): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->user->business_id,
            'name' => $roleName,
        ]);

        $this->user->forceFill(['role_id' => $role->id])->save();

        $this->actingAs($this->user->fresh());
    }

    protected function product(int $quantity = 10, float $costPrice = 500.00): Product
    {
        return Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => $quantity,
            'cost_price' => $costPrice,
            'status' => 'active',
        ]);
    }

    public function test_write_off_creates_product_loss_record(): void
    {
        $product = $this->product(10, 500.00);

        $loss = $this->inventoryService->writeOff($product, 3, 'damaged', 'Crushed in storage');

        $this->assertInstanceOf(ProductLoss::class, $loss);
        $this->assertSame('damaged', $loss->type);
        $this->assertSame(3, $loss->quantity);
        $this->assertSame(50000, $loss->unit_cost);
        $this->assertSame(150000, $loss->total_loss);
        $this->assertSame(7, $product->fresh()->quantity);
    }

    public function test_write_off_creates_stock_movement(): void
    {
        $product = $this->product(10);

        $loss = $this->inventoryService->writeOff($product, 3, 'expired', 'Past shelf life');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'reason' => 'expired',
            'quantity' => 3,
        ]);

        $movement = StockMovement::find($loss->stock_movement_id);
        $this->assertNotNull($movement);
    }

    public function test_write_off_creates_cash_flow_expense(): void
    {
        $product = $this->product(10, 500.00);

        $loss = $this->inventoryService->writeOff($product, 3, 'lost', 'Missing after stocktake');

        $this->assertDatabaseHas('cash_flows', [
            'type' => 'expense',
            'category' => 'inventory_loss',
            'amount' => 1500.00,
        ]);
    }

    public function test_cannot_write_off_more_than_available_stock(): void
    {
        $product = $this->product(2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->inventoryService->writeOff($product, 5, 'damaged', 'Over-write attempt');
    }

    public function test_write_off_is_idempotent_per_movement_key(): void
    {
        $product = $this->product(10);

        $key = md5($product->id.':writeoff:damaged:3:'.now()->toDateString());

        $loss1 = $this->inventoryService->writeOff($product, 3, 'damaged', 'First call', $key);
        $loss2 = $this->inventoryService->writeOff($product, 3, 'damaged', 'Retry', $key);

        $this->assertSame($loss1->id, $loss2->id);
        $this->assertSame(7, $product->fresh()->quantity);
        $this->assertSame(1, ProductLoss::where('stock_movement_id', $loss1->stock_movement_id)->count());
    }

    public function test_product_loss_via_http_endpoint(): void
    {
        $this->actingAsRole('BranchManager');
        $product = $this->product(10, 500.00);

        $response = $this->postJson('/api/product-losses', [
            'product_id' => $product->id,
            'type' => 'expired',
            'quantity' => 4,
            'reason' => 'Expired crate',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_losses', [
            'product_id' => $product->id,
            'type' => 'expired',
            'quantity' => 4,
            'total_loss' => 200000,
        ]);
        $this->assertSame(6, $product->fresh()->quantity);
    }

    public function test_product_loss_summary_endpoint(): void
    {
        $product = $this->product(10, 500.00);

        $this->inventoryService->writeOff($product, 2, 'damaged', 'Breakage');
        $this->inventoryService->writeOff($product->fresh(), 3, 'lost', 'Theft');

        $response = $this->getJson('/api/product-losses/summary');

        $response->assertStatus(200);
        $this->assertEquals(1000.00, $response->json('by_type.damaged.total'));
        $this->assertEquals(1500.00, $response->json('by_type.lost.total'));
        $this->assertEquals(2500.00, $response->json('grand_total'));
    }

    public function test_expiry_sweep_writes_off_expired_products(): void
    {
        $expired = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 5,
            'cost_price' => 200.00,
            'expiry_date' => now()->subDays(3)->toDateString(),
            'status' => 'active',
        ]);

        $fresh = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 5,
            'cost_price' => 200.00,
            'expiry_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        $this->artisan('inventory:sweep-expired')
            ->assertSuccessful();

        $this->assertDatabaseHas('product_losses', [
            'product_id' => $expired->id,
            'type' => 'expired',
            'quantity' => 5,
        ]);
        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame(5, $fresh->fresh()->quantity);
        $this->assertSame(0, ProductLoss::where('product_id', $fresh->id)->count());
    }
}
