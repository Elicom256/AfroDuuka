<?php

namespace Tests\Feature\POS;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\HeldCart;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosHeldCartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Business $business;
    protected BusinessBranch $branch;
    protected Product $product;
    protected Customer $customer;

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
            'quantity' => 50,
            'price' => 10000,
            'status' => 'active',
        ]);
        $customerUser = User::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->customer = Customer::factory()->create([
            'user_id' => $customerUser->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_hold_cart(): void
    {
        $response = $this->postJson('/api/pos/cart/hold', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                ],
            ],
            'customer_id' => $this->customer->id,
            'notes' => 'Customer will return later',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Cart held successfully']);

        $this->assertDatabaseHas('held_carts', [
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'notes' => 'Customer will return later',
        ]);
    }

    public function test_cannot_hold_empty_cart(): void
    {
        $response = $this->postJson('/api/pos/cart/hold', [
            'items' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_can_list_held_carts(): void
    {
        HeldCart::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'items' => [['product_id' => 1, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $response = $this->getJson('/api/pos/cart/held');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_can_resume_cart(): void
    {
        $heldCart = HeldCart::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10000]],
        ]);

        $response = $this->getJson("/api/pos/cart/resume/{$heldCart->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Cart resumed']);
    }

    public function test_can_delete_held_cart(): void
    {
        $heldCart = HeldCart::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10000]],
        ]);

        $response = $this->deleteJson("/api/pos/cart/held/{$heldCart->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Held cart deleted']);

        $this->assertDatabaseMissing('held_carts', ['id' => $heldCart->id]);
    }

    public function test_cannot_resume_others_cart(): void
    {
        $otherUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
        ]);

        $heldCart = HeldCart::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $otherUser->id,
            'items' => [['product_id' => 1, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $this->getJson("/api/pos/cart/resume/{$heldCart->id}")
            ->assertStatus(404);
    }

    public function test_hold_cart_does_not_affect_inventory(): void
    {
        $initialQty = $this->product->quantity;

        $this->postJson('/api/pos/cart/hold', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 10000,
                ],
            ],
        ]);

        $this->product->refresh();
        $this->assertEquals($initialQty, $this->product->quantity);
    }
}
