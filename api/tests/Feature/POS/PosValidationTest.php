<?php

namespace Tests\Feature\POS;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Business $business;
    protected BusinessBranch $branch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $role = Role::factory()->create(['business_id' => $this->business->id]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $this->product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 5,
            'price' => 10000,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_cart_validation_passes(): void
    {
        $response = $this->postJson('/api/pos/cart/validate', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    public function test_cart_validation_fails_for_overselling(): void
    {
        $response = $this->postJson('/api/pos/cart/validate', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 100],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_cart_validation_fails_for_inactive_product(): void
    {
        $inactiveProduct = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'status' => 'discontinued',
        ]);

        $response = $this->postJson('/api/pos/cart/validate', [
            'items' => [
                ['product_id' => $inactiveProduct->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_cart_validation_fails_for_product_from_other_branch(): void
    {
        $otherBranch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $otherProduct = Product::factory()->create([
            'business_branch_id' => $otherBranch->id,
            'quantity' => 10,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/pos/cart/validate', [
            'items' => [
                ['product_id' => $otherProduct->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_requires_items(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'payments' => [['method' => 'cash', 'amount' => 100]],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_requires_payments(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        $response->assertStatus(422);
    }
}
