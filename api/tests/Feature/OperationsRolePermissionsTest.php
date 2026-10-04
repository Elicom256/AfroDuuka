<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Operations runs the floor: it counts stock, records adjustments and sells. It does
 * not author the catalogue and it does not remove records.
 *
 * These assert the API, not the UI. The hide-a-button approach that predates this only
 * ever stopped the button — the endpoints were open to any bearer token with the right
 * business_branch_id.
 */
class OperationsRolePermissionsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;

    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
    }

    private function actingAsRole(string $roleName): User
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

        Sanctum::actingAs($user);

        return $user;
    }

    private function product(array $overrides = []): Product
    {
        return Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 1500,
            'name' => 'Counted Widget',
        ] + $overrides);
    }

    public function test_operations_cannot_create_a_product(): void
    {
        $this->actingAsRole('Operations');

        $response = $this->postJson('/api/products', [
            'name' => 'Unauthorized Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('products', ['name' => 'Unauthorized Widget']);
    }

    public function test_operations_cannot_delete_a_product(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product();

        $this->deleteJson("/api/products/{$product->id}")->assertStatus(403);

        // The product cascades into stock_movements and sale_items, so a delete here
        // would take the sales history with it.
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_operations_cannot_delete_a_product_category(): void
    {
        $this->actingAsRole('Operations');
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Locked Category',
            'description' => 'Should survive',
            'status' => 'active',
        ]);

        $this->deleteJson("/api/products/categories/{$category->id}")->assertStatus(403);

        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
    }

    public function test_delete_is_refused_even_for_a_product_they_can_read(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product();

        // Readable, therefore in branch, therefore a delete the naive policy allowed.
        $this->getJson("/api/products/{$product->id}")->assertStatus(200);

        $this->deleteJson("/api/products/{$product->id}")->assertStatus(403);
    }

    public function test_operations_can_adjust_stock(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product(['quantity' => 10]);

        $this->putJson("/api/products/{$product->id}", [
            'quantity' => 24,
            'adjustment_reason' => 'stock_take',
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 24]);

        // The count is the observed level; the movement records what actually changed.
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 14,
            'reason' => 'stock_take',
        ]);
    }

    public function test_operations_can_reduce_stock_by_an_adjustment(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product(['quantity' => 10]);

        $this->putJson("/api/products/{$product->id}", [
            'quantity' => 6,
            'adjustment_reason' => 'damaged',
            'adjustment_notes' => 'Crushed in transit',
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 6]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 4,
            'reason' => 'damaged',
        ]);
    }

    public function test_a_stock_take_that_finds_no_difference_writes_no_movement(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product(['quantity' => 10]);

        $this->putJson("/api/products/{$product->id}", [
            'quantity' => 10,
            'adjustment_reason' => 'stock_take',
        ])->assertStatus(201);

        $this->assertSame(0, StockMovement::where('product_id', $product->id)->count());
    }

    public function test_operations_cannot_rename_a_product(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product();

        $this->putJson("/api/products/{$product->id}", [
            'quantity' => 12,
            'name' => 'Renamed Widget',
        ])->assertStatus(422);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Counted Widget']);
    }

    public function test_operations_cannot_reprice_a_product(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product();

        $this->putJson("/api/products/{$product->id}", [
            'selling_price' => 1,
        ])->assertStatus(422);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'selling_price' => 1500]);
    }

    public function test_operations_cannot_change_a_product_status(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->product();

        $this->putJson("/api/products/{$product->id}", [
            'status' => 'discontinued',
        ])->assertStatus(422);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'active']);
    }

    public function test_operations_cannot_create_a_product_category(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/products/categories', [
            'name' => 'Unauthorized Category',
            'description' => 'Should never exist',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('product_categories', ['name' => 'Unauthorized Category']);
    }

    public function test_operations_can_still_read_the_catalogue(): void
    {
        $this->actingAsRole('Operations');
        $this->product();

        $this->getJson('/api/products')->assertStatus(200);
    }

    public function test_an_executive_can_still_create_a_product(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/products', [
            'name' => 'Executive Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', ['name' => 'Executive Widget']);
    }

    public function test_an_executive_can_still_reprice_a_product(): void
    {
        $this->actingAsRole('Executive');
        $product = $this->product();

        $this->putJson("/api/products/{$product->id}", [
            'selling_price' => 2000,
            'change_reason' => 'Price adjustment',
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'selling_price' => 2000]);
    }

    public function test_procurement_cannot_reprice_a_product(): void
    {
        $this->actingAsRole('Procurement');
        $product = $this->product();

        $this->putJson("/api/products/{$product->id}", [
            'selling_price' => 2000,
        ])->assertStatus(403);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'selling_price' => 1500,
        ]);
    }

    public function test_an_executive_can_still_delete_a_product(): void
    {
        $this->actingAsRole('Executive');
        $product = $this->product();

        $this->deleteJson("/api/products/{$product->id}")->assertStatus(201);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_an_executive_stock_count_still_lands_directly(): void
    {
        $this->actingAsRole('Executive');
        $product = $this->product(['quantity' => 10]);

        $this->putJson("/api/products/{$product->id}", ['quantity' => 30])->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 30]);
    }

    public function test_the_role_name_is_matched_case_insensitively(): void
    {
        $this->actingAsRole('operations');
        $product = $this->product();

        $this->deleteJson("/api/products/{$product->id}")->assertStatus(403);

        $this->postJson('/api/products', [
            'name' => 'Lowercase Role Widget',
            'quantity' => 1,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ])->assertStatus(403);
    }

    public function test_a_refused_create_is_403_regardless_of_the_payload(): void
    {
        $this->actingAsRole('Operations');

        // A malformed body must not leak as 422: that would imply the role was allowed
        // to attempt the create and merely got the fields wrong.
        $this->postJson('/api/products', ['quantity' => 1])->assertStatus(403);

        $this->assertDatabaseMissing('products', ['name' => 'Malformed Widget']);
    }

    /**
     * The rest of this class uses Sanctum::actingAs(), which resolves the user before
     * any middleware runs. That would let BlockRestrictedRoleActions pass on an
     * unresolved request user and still return 403-free responses, so the ordering
     * between auth:sanctum and the deny-rule goes untested.
     *
     * This signs in for real — a bearer token through the actual middleware stack — so
     * the user only becomes known when Authenticate resolves it. If the deny-rule were
     * sorted ahead of auth it would see a null user, wave the delete through, and this
     * test would find the product gone.
     */
    public function test_a_bearer_token_delete_is_refused_end_to_end(): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Operations',
        ]);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $product = $this->product();

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_a_bearer_token_create_is_refused_end_to_end(): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Operations',
        ]);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/products', [
                'name' => 'Bearer Widget',
                'quantity' => 1,
                'cost_price' => 1000,
                'selling_price' => 1500,
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('products', ['name' => 'Bearer Widget']);
    }

    public function test_a_bearer_token_stock_adjustment_still_works(): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Operations',
        ]);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $product = $this->product(['quantity' => 10]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/products/{$product->id}", [
                'quantity' => 18,
                'adjustment_reason' => 'stock_take',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 18]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 8,
        ]);
    }

    /**
     * The product cases above are also covered by ProductPolicy, so on their own they
     * would pass even if BlockRestrictedRoleActions never ran.
     *
     * This hits a delete that no controller authorises at all — 49 of them have a
     * destroy() with no authorize() call, and TaxCategoryController is one. Nothing
     * but the deny-rule stands between this token and the row, which is the whole
     * reason the rule is middleware rather than a policy on one model.
     */
    public function test_an_unauthorised_delete_endpoint_is_covered_by_the_deny_rule(): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Operations',
        ]);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $taxCategory = TaxCategory::factory()->create([
            'business_branch_id' => $this->branch->id,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/tax-categories/{$taxCategory->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('tax_categories', ['id' => $taxCategory->id]);
    }

    public function test_the_deny_rule_does_not_block_non_destructive_verbs(): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Operations',
        ]);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $product = $this->product(['quantity' => 10]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // POST is how a restricted role sells, and PUT is how it books a stock count.
        $this->withToken($token)
            ->putJson("/api/products/{$product->id}", ['quantity' => 11])
            ->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'quantity' => 11]);
    }
}
