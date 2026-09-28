<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function branchUser(Business $business, ?BusinessBranch $branch = null): User
    {
        $branch ??= BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id, 'name' => 'Executive']);

        return User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);
    }

    protected function coreAdmin(Business $business): User
    {
        $role = Role::factory()->create(['business_id' => $business->id, 'name' => 'Executive']);

        return User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => null,
            'role_id' => $role->id,
        ]);
    }

    protected function superAdmin(): User
    {
        $role = Role::factory()->create(['name' => 'CoreSupport']);

        return User::factory()->create([
            'business_id' => null,
            'business_branch_id' => null,
            'role_id' => $role->id,
        ]);
    }

    public function test_branch_user_cannot_read_product_from_another_branch_in_same_business(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $foreignProduct = Product::factory()->create(['business_branch_id' => $branchB->id]);

        Sanctum::actingAs($this->branchUser($business, $branchA));

        $this->getJson("/api/products/{$foreignProduct->id}")
            ->assertStatus(404);
    }

    public function test_branch_user_cannot_read_product_from_a_different_business(): void
    {
        $businessA = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $businessA->id]);

        $businessB = Business::factory()->create();
        $branchB = BusinessBranch::factory()->create(['business_id' => $businessB->id]);
        $foreignProduct = Product::factory()->create(['business_branch_id' => $branchB->id]);

        Sanctum::actingAs($this->branchUser($businessA, $branchA));

        $this->getJson("/api/products/{$foreignProduct->id}")
            ->assertStatus(404);
    }

    public function test_branch_user_cannot_update_product_from_another_branch(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $foreignProduct = Product::factory()->create(['business_branch_id' => $branchB->id]);

        Sanctum::actingAs($this->branchUser($business, $branchA));

        $this->putJson("/api/products/{$foreignProduct->id}", ['selling_price' => 9999])
            ->assertStatus(404);

        $this->assertDatabaseHas('products', ['id' => $foreignProduct->id]);
    }

    public function test_branch_user_cannot_delete_product_from_another_branch(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $foreignProduct = Product::factory()->create(['business_branch_id' => $branchB->id]);

        Sanctum::actingAs($this->branchUser($business, $branchA));

        $this->deleteJson("/api/products/{$foreignProduct->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('products', ['id' => $foreignProduct->id]);
    }

    public function test_branch_user_index_only_contains_own_branch_products(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $ownProduct = Product::factory()->create(['business_branch_id' => $branchA->id, 'name' => 'Own Branch Widget']);
        Product::factory()->create(['business_branch_id' => $branchB->id, 'name' => 'Other Branch Widget']);

        Sanctum::actingAs($this->branchUser($business, $branchA));

        $response = $this->getJson('/api/products')->assertStatus(200);

        $this->assertCount(1, $response->json('products'));
        $this->assertEquals($ownProduct->id, $response->json('products.0.id'));
    }

    public function test_core_admin_index_contains_products_from_all_business_branches(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        Product::factory()->create(['business_branch_id' => $branchA->id, 'name' => 'Branch A Widget']);
        Product::factory()->create(['business_branch_id' => $branchB->id, 'name' => 'Branch B Widget']);

        Sanctum::actingAs($this->coreAdmin($business));

        $response = $this->getJson('/api/products')->assertStatus(200);

        $this->assertCount(2, $response->json('products'));
    }

    public function test_core_admin_cannot_read_product_from_another_business(): void
    {
        $businessA = Business::factory()->create();
        BusinessBranch::factory()->create(['business_id' => $businessA->id]);

        $businessB = Business::factory()->create();
        $branchB = BusinessBranch::factory()->create(['business_id' => $businessB->id]);
        $foreignProduct = Product::factory()->create(['business_branch_id' => $branchB->id]);

        Sanctum::actingAs($this->coreAdmin($businessA));

        $this->getJson("/api/products/{$foreignProduct->id}")
            ->assertStatus(404);
    }

    public function test_core_admin_can_create_product_in_any_branch_of_own_business(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        Sanctum::actingAs($this->coreAdmin($business));

        $this->postJson('/api/products', [
            'name' => 'Admin Branch B Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
            'business_branch_id' => $branchB->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'name' => 'Admin Branch B Widget',
            'business_branch_id' => $branchB->id,
        ]);
    }

    public function test_branch_user_cannot_create_product_in_a_different_branch(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        Sanctum::actingAs($this->branchUser($business, $branchA));

        $this->postJson('/api/products', [
            'name' => 'Sneaky Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
            'business_branch_id' => $branchB->id,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('products', ['name' => 'Sneaky Widget']);
    }

    public function test_branch_user_can_create_product_without_supplying_branch(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);

        Sanctum::actingAs($this->branchUser($business, $branchA));

        $this->postJson('/api/products', [
            'name' => 'Self Assigned Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'name' => 'Self Assigned Widget',
            'business_branch_id' => $branchA->id,
        ]);
    }

    public function test_coresupport_without_business_sees_products_from_all_businesses(): void
    {
        $businessA = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $businessA->id]);
        Product::factory()->create(['business_branch_id' => $branchA->id, 'name' => 'Business A Widget']);

        $businessB = Business::factory()->create();
        $branchB = BusinessBranch::factory()->create(['business_id' => $businessB->id]);
        Product::factory()->create(['business_branch_id' => $branchB->id, 'name' => 'Business B Widget']);

        Sanctum::actingAs($this->superAdmin());

        $response = $this->getJson('/api/products')->assertStatus(200);

        $this->assertCount(2, $response->json('products'));
    }

    public function test_product_global_scope_hides_products_outside_the_branch_set(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $ownProduct = Product::factory()->create(['business_branch_id' => $branchA->id]);
        $otherProduct = Product::factory()->create(['business_branch_id' => $branchB->id]);

        Sanctum::actingAs($this->branchUser($business, $branchA));

        $this->assertTrue(Product::find($ownProduct->id) instanceof Product);
        $this->assertNull(Product::find($otherProduct->id));
    }
}
