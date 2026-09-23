<?php

namespace Tests\Feature\Tax;

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

class TaxCategoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Business $business;
    protected BusinessBranch $branch;
    protected BusinessBranch $otherBranch;

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
        $role = Role::factory()->create(['business_id' => $this->business->id]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_create_tax_category(): void
    {
        $response = $this->postJson('/api/tax-categories', [
            'name' => 'VAT',
            'description' => 'Value Added Tax',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('category.name', 'VAT')
            ->assertJsonPath('category.is_active', true)
            ->assertJsonPath('category.business_branch_id', $this->branch->id);

        $this->assertDatabaseHas('tax_categories', [
            'business_branch_id' => $this->branch->id,
            'name' => 'VAT',
        ]);
    }

    public function test_can_list_tax_categories_for_own_branch(): void
    {
        TaxCategory::factory()->create(['business_branch_id' => $this->branch->id, 'name' => 'VAT']);
        TaxCategory::factory()->create(['business_branch_id' => $this->otherBranch->id, 'name' => 'Other Branch VAT']);

        $response = $this->getJson('/api/tax-categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'categories' => [['id', 'name', 'is_active', 'rates']]]);

        $names = collect($response->json('categories'))->pluck('name');
        $this->assertTrue($names->contains('VAT'));
        $this->assertFalse($names->contains('Other Branch VAT'));
    }

    public function test_can_retrieve_tax_category(): void
    {
        $category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);

        $this->getJson("/api/tax-categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('category.id', $category->id);
    }

    public function test_can_update_tax_category(): void
    {
        $category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);

        $this->putJson("/api/tax-categories/{$category->id}", [
            'name' => 'Updated VAT',
            'description' => 'Updated description',
        ])
            ->assertStatus(200)
            ->assertJsonPath('category.name', 'Updated VAT');

        $this->assertDatabaseHas('tax_categories', [
            'id' => $category->id,
            'name' => 'Updated VAT',
        ]);
    }

    public function test_can_deactivate_tax_category(): void
    {
        $category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);

        $this->putJson("/api/tax-categories/{$category->id}", [
            'is_active' => false,
        ])
            ->assertStatus(200)
            ->assertJsonPath('category.is_active', false);
    }

    public function test_user_cannot_access_another_branch_category(): void
    {
        $otherCategory = TaxCategory::factory()->create(['business_branch_id' => $this->otherBranch->id]);

        $this->getJson("/api/tax-categories/{$otherCategory->id}")->assertStatus(404);
        $this->putJson("/api/tax-categories/{$otherCategory->id}", ['name' => 'Hijack'])->assertStatus(404);
        $this->deleteJson("/api/tax-categories/{$otherCategory->id}")->assertStatus(404);
    }

    public function test_cannot_delete_category_in_use_by_products(): void
    {
        $category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);

        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $category->id,
        ]);

        $this->deleteJson("/api/tax-categories/{$category->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('tax_categories', ['id' => $category->id]);
    }

    public function test_can_delete_unused_tax_category(): void
    {
        $category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);

        $this->deleteJson("/api/tax-categories/{$category->id}")->assertStatus(200);

        $this->assertDatabaseMissing('tax_categories', ['id' => $category->id]);
    }
}