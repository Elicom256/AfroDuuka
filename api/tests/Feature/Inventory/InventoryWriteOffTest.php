<?php

namespace Tests\Feature\Inventory;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWriteOffTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_off_reduces_stock_and_records_reason(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);

        $product = Product::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'quantity' => 12,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $service = app(InventoryService::class);
        $movement = $service->writeOff($product, 3, 'damaged', 'Expired crate found in storage');

        $this->assertSame(9, $product->fresh()->quantity);
        $this->assertSame('adjustment', $movement->type);
        $this->assertSame('damaged', $movement->reason);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'reason' => 'damaged',
            'quantity' => 3,
        ]);
    }
}
