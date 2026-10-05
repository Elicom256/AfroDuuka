<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A purchase line is a financial record, so removing it must not erase it.
 *
 * `purchase_items` has a `deleted_at` column and the model had no SoftDeletes trait, so
 * nothing ever wrote to it and nothing ever filtered on it. A deleted line was therefore
 * indistinguishable from one that had never existed (checked.md P2, backend).
 *
 * PurchaseItem extends Eloquent\Model rather than BaseModel, so it carries no tenant
 * scope of its own. These tests use one business so they are about soft deletes and not
 * about scoping, and they assert through the model rather than the API because
 * PurchaseItemController's routes are unrouted.
 */
class PurchaseItemSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Purchase $purchase;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => Role::factory()->create(['business_id' => $business->id])->id,
        ]);

        $product = Product::factory()->create(['business_branch_id' => $branch->id]);

        $this->purchase = Purchase::create([
            'business_branch_id' => $branch->id,
            'status' => 'completed',
            'total_amount' => 0,
        ]);

        PurchaseItem::create([
            'purchase_id' => $this->purchase->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'cost_price' => 10000,
            'subtotal' => 50000,
        ]);
    }

    public function test_the_model_soft_deletes(): void
    {
        $item = PurchaseItem::firstOrFail();

        $this->assertNull($item->deleted_at);

        $item->delete();

        $this->assertNotNull($item->fresh()->deleted_at, 'The row should be kept with a deleted_at.');
    }

    public function test_a_deleted_line_leaves_the_normal_queries(): void
    {
        PurchaseItem::firstOrFail()->delete();

        $this->assertSame(0, PurchaseItem::count(), 'A deleted line must not appear in the normal listing.');
    }

    public function test_a_deleted_line_is_still_recoverable(): void
    {
        $item = PurchaseItem::firstOrFail();
        $item->delete();

        $this->assertSame(1, PurchaseItem::onlyTrashed()->count());
        $this->assertSame(0, PurchaseItem::withTrashed()->count() - 1);

        $item->restore();

        $this->assertNull($item->fresh()->deleted_at);
        $this->assertSame(1, PurchaseItem::count());
    }

    public function test_the_column_is_actually_written(): void
    {
        // Guards the trait rather than the intent: without it the delete is a hard delete
        // and the row is gone, which the other two tests would also notice, but this one
        // pins that deleted_at is what changed.
        $item = PurchaseItem::firstOrFail();
        $item->delete();

        $this->assertDatabaseHas('purchase_items', [
            'id' => $item->id,
            'deleted_at' => $item->fresh()->deleted_at,
        ]);
    }
}
