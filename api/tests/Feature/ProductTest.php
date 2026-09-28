<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Business $business;
    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'Executive']);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_product_can_be_created_with_cost_and_selling_price(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Test Widget',
            'quantity' => 10,
            'cost_price' => 5000,
            'selling_price' => 7500,
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Product Created Successfully!']);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Widget',
            'business_branch_id' => $this->branch->id,
            'cost_price' => 5000.00,
            'selling_price' => 7500.00,
            'is_tax_inclusive' => false,
        ]);
    }

    public function test_product_can_be_created_with_a_tax_category(): void
    {
        $taxCategory = TaxCategory::factory()->create([
            'business_branch_id' => $this->branch->id,
        ]);

        $response = $this->postJson('/api/products', [
            'name' => 'Taxed Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
            'is_tax_inclusive' => true,
            'tax_category_id' => $taxCategory->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'name' => 'Taxed Widget',
            'tax_category_id' => $taxCategory->id,
            'is_tax_inclusive' => true,
        ]);
    }

    public function test_product_can_be_created_without_a_tax_category(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Untaxed Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'name' => 'Untaxed Widget',
            'tax_category_id' => null,
        ]);
    }

    public function test_is_tax_inclusive_is_persisted_correctly(): void
    {
        $this->postJson('/api/products', [
            'name' => 'Inclusive Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
            'is_tax_inclusive' => true,
        ])->assertStatus(201);

        $this->postJson('/api/products', [
            'name' => 'Exclusive Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
            'is_tax_inclusive' => false,
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'name' => 'Inclusive Widget',
            'is_tax_inclusive' => true,
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'Exclusive Widget',
            'is_tax_inclusive' => false,
        ]);
    }

    public function test_product_can_be_updated(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'selling_price' => 2000,
            'change_reason' => 'Price adjustment',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Product Updated Successfully!']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'selling_price' => 2000.00,
        ]);

        $this->assertDatabaseHas('price_histories', [
            'product_id' => $product->id,
            'old_sale_price' => 1500.00,
            'new_sale_price' => 2000.00,
        ]);
    }

    public function test_selling_price_is_returned_in_api_response(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'cost_price' => 100,
            'selling_price' => 150,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertArrayHasKey('selling_price', $response->json('product'));
        $this->assertArrayNotHasKey('price', $response->json('product'));
    }

    public function test_markup_percentage_is_calculated_correctly(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'cost_price' => 100,
            'selling_price' => 150,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('product.markup_percentage', 50);
    }

    public function test_zero_cost_price_does_not_divide_by_zero(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'cost_price' => 0,
            'selling_price' => 150,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('product.markup_percentage', null);
    }

    public function test_product_cannot_reference_tax_category_from_another_branch(): void
    {
        $otherBranch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $foreignTaxCategory = TaxCategory::factory()->create([
            'business_branch_id' => $otherBranch->id,
        ]);

        $response = $this->postJson('/api/products', [
            'name' => 'Cross Tenant Widget',
            'quantity' => 5,
            'cost_price' => 1000,
            'selling_price' => 1500,
            'tax_category_id' => $foreignTaxCategory->id,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('products', [
            'name' => 'Cross Tenant Widget',
        ]);
    }

    public function test_existing_product_functionality_remains_intact(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Listable Widget',
            'quantity' => 5,
            'cost_price' => 100,
            'selling_price' => 150,
            'status' => 'active',
        ]);

        $this->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonPath('products.0.name', 'Listable Widget');

        $this->deleteJson("/api/products/{$product->id}")
            ->assertStatus(201);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }
}