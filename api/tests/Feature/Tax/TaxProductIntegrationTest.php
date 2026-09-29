<?php

namespace Tests\Feature\Tax;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaxProductIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Business $business;
    protected BusinessBranch $branch;
    protected BusinessBranch $otherBranch;
    protected TaxCategory $category;
    protected TaxCategory $otherCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->otherBranch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        // Named explicitly: RoleFactory defaults to 'Operations', which may not author
        // catalogue records, and these cases are about which tax category a product is
        // allowed to point at — not about who may create the product. See
        // OperationsRolePermissionsTest for the role boundary.
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Executive',
        ]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);
        $this->otherCategory = TaxCategory::factory()->create(['business_branch_id' => $this->otherBranch->id]);

        Sanctum::actingAs($this->user);
    }

    public function test_product_can_reference_a_tax_category(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Taxed Widget',
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 1180,
            'is_tax_inclusive' => true,
            'tax_category_id' => $this->category->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('product.tax_category_id', $this->category->id)
            ->assertJsonPath('product.is_tax_inclusive', true);

        $this->assertDatabaseHas('products', [
            'name' => 'Taxed Widget',
            'tax_category_id' => $this->category->id,
        ]);
    }

    public function test_product_can_have_no_tax_category(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Untaxed Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 2000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('product.tax_category_id', null);

        $this->assertDatabaseHas('products', [
            'name' => 'Untaxed Widget',
            'tax_category_id' => null,
        ]);
    }

    public function test_product_cannot_reference_another_branch_tax_category(): void
    {
        $this->postJson('/api/products', [
            'name' => 'Hijacked Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 2000,
            'tax_category_id' => $this->otherCategory->id,
        ])->assertStatus(422);
    }

    public function test_product_response_includes_tax_category_relation(): void
    {
        $product = \App\Models\Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
        ]);

        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('product.tax_category.id', $this->category->id);
    }
}