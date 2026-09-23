<?php

namespace Tests\Feature\Tax;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\TaxCategory;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaxRateTest extends TestCase
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
        $role = Role::factory()->create(['business_id' => $this->business->id]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);
        $this->otherCategory = TaxCategory::factory()->create(['business_branch_id' => $this->otherBranch->id]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_create_rate(): void
    {
        $response = $this->postJson('/api/tax-rates', [
            'tax_category_id' => $this->category->id,
            'name' => 'VAT Standard',
            'rate' => '0.18',
            'jurisdiction_zone' => 'Uganda',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('rate.name', 'VAT Standard')
            ->assertJsonPath('rate.rate', 0.18)
            ->assertJsonPath('rate.tax_category_id', $this->category->id);

        $this->assertDatabaseHas('tax_rates', [
            'tax_category_id' => $this->category->id,
            'name' => 'VAT Standard',
            'rate' => 0.18,
        ]);
    }

    public function test_can_update_rate(): void
    {
        $rate = TaxRate::factory()->create(['tax_category_id' => $this->category->id]);

        $this->putJson("/api/tax-rates/{$rate->id}", [
            'rate' => '0.20',
            'is_active' => false,
        ])
            ->assertStatus(200)
            ->assertJsonPath('rate.rate', 0.20)
            ->assertJsonPath('rate.is_active', false);
    }

    public function test_can_list_rates_for_own_branch(): void
    {
        TaxRate::factory()->create(['tax_category_id' => $this->category->id, 'name' => 'Standard']);
        TaxRate::factory()->create(['tax_category_id' => $this->otherCategory->id, 'name' => 'Foreign']);

        $response = $this->getJson('/api/tax-rates');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'rates' => [['id', 'name', 'rate', 'tax_category_id']]]);

        $names = collect($response->json('rates'))->pluck('name');
        $this->assertTrue($names->contains('Standard'));
        $this->assertFalse($names->contains('Foreign'));
    }

    public function test_rate_must_be_decimal_between_zero_and_one(): void
    {
        $this->postJson('/api/tax-rates', [
            'tax_category_id' => $this->category->id,
            'name' => 'Bad Rate',
            'rate' => '18',
        ])->assertStatus(422);

        $this->postJson('/api/tax-rates', [
            'tax_category_id' => $this->category->id,
            'name' => 'Bad Rate',
            'rate' => '-0.05',
        ])->assertStatus(422);
    }

    public function test_rate_must_belong_to_a_category_in_own_branch(): void
    {
        $this->postJson('/api/tax-rates', [
            'tax_category_id' => $this->otherCategory->id,
            'name' => 'Cross Tenant',
            'rate' => '0.18',
        ])->assertStatus(422);
    }

    public function test_user_cannot_access_another_branch_rate(): void
    {
        $foreignRate = TaxRate::factory()->create(['tax_category_id' => $this->otherCategory->id]);

        $this->getJson("/api/tax-rates/{$foreignRate->id}")->assertStatus(404);
        $this->putJson("/api/tax-rates/{$foreignRate->id}", ['rate' => '0.30'])->assertStatus(404);
        $this->deleteJson("/api/tax-rates/{$foreignRate->id}")->assertStatus(404);
    }

    public function test_can_delete_rate(): void
    {
        $rate = TaxRate::factory()->create(['tax_category_id' => $this->category->id]);

        $this->deleteJson("/api/tax-rates/{$rate->id}")->assertStatus(200);

        $this->assertDatabaseMissing('tax_rates', ['id' => $rate->id]);
    }
}