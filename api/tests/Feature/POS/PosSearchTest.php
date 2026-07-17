<?php

namespace Tests\Feature\POS;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosSearchTest extends TestCase
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
        $role = Role::factory()->create(['business_id' => $this->business->id]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_search_products_by_name(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Test Product Alpha',
            'sku' => 'TST-001',
            'barcode' => '123456789',
            'quantity' => 10,
            'price' => 5000,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/pos/products/search?q=Alpha');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_can_search_products_by_barcode(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Barcode Product',
            'sku' => 'BRC-001',
            'barcode' => '987654321',
            'quantity' => 5,
            'price' => 2500,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/pos/products/search?q=987654321');

        $response->assertStatus(200);
    }

    public function test_can_search_products_by_sku(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'SKU Product',
            'sku' => 'SKU-999',
            'barcode' => '111111111',
            'quantity' => 20,
            'price' => 15000,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/pos/products/search?q=SKU-999');

        $response->assertStatus(200);
    }

    public function test_search_products_returns_empty_for_no_match(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Unique Product',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/pos/products/search?q=NONEXISTENT');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_can_search_customers_by_name(): void
    {
        $customerUser = User::factory()->create([
            'business_id' => $this->business->id,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'phone' => '0700123456',
        ]);
        Customer::factory()->create([
            'user_id' => $customerUser->id,
        ]);

        $response = $this->getJson('/api/pos/customers/search?q=John');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    public function test_search_requires_auth(): void
    {
        $this->getJson('/api/pos/products/search?q=test')
            ->assertStatus(302);
    }
}
