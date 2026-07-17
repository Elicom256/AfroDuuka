<?php

namespace Tests\Feature\POS;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
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
        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'admin']);
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

    public function test_can_complete_checkout(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 20000],
            ],
            'customer_id' => $this->customer->id,
            'note' => 'Test POS sale',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sale completed successfully!']);

        $this->assertDatabaseHas('sales', [
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 20000,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 10000,
        ]);

        $this->assertDatabaseHas('sale_payments', [
            'method' => 'cash',
            'amount' => 20000,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cash_flows', [
            'type' => 'sale',
            'amount' => 20000,
        ]);

        $this->assertDatabaseHas('receipts', [
            'amount_paid' => 20000,
        ]);

        $this->product->refresh();
        $this->assertEquals(48, $this->product->quantity);
    }

    public function test_checkout_fails_when_cart_empty(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'items' => [],
            'payments' => [['method' => 'cash', 'amount' => 0]],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_fails_when_insufficient_stock(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 999,
                    'unit_price' => 10000,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 9990000],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_checkout_with_split_payments(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_price' => 10000,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 15000],
                ['method' => 'mobile_money', 'amount' => 15000],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sale_payments', ['method' => 'cash', 'amount' => 15000]);
        $this->assertDatabaseHas('sale_payments', ['method' => 'mobile_money', 'amount' => 15000]);
    }

    public function test_checkout_with_discount(): void
    {
        $response = $this->postJson('/api/pos/checkout', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 10000,
                    'discount' => 1000,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 45000],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $this->product->id,
            'discount' => 1000,
            'subtotal' => 45000,
        ]);
    }

    public function test_checkout_requires_auth(): void
    {
        $this->postJson('/api/pos/checkout', [])
            ->assertStatus(302);
    }

    public function test_checkout_updates_last_sold_at(): void
    {
        $this->postJson('/api/pos/checkout', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 10000],
            ],
        ]);

        $this->product->refresh();
        $this->assertNotNull($this->product->last_sold_at);
    }
}
