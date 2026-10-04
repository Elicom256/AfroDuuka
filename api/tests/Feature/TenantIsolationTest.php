<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\TaxCategory;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\SaleItemService;
use App\Support\Tenant\BusinessContext;
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

    public function test_sale_item_service_rejects_products_belonging_to_a_different_branch(): void
    {
        $business = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $branchB = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $user = $this->branchUser($business, $branchA);
        $foreignProduct = Product::factory()->create([
            'business_branch_id' => $branchB->id,
            'quantity' => 10,
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        $paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($user);

        $service = app(SaleItemService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('selected branch');

        $service->handleSaveSaleItem([
            'business_branch_id' => $branchA->id,
            'customer_id' => null,
            'note' => 'Branch mismatch attempt',
            'paymentStatus' => 'paid',
            'payment_status_id' => $paymentMethod->id,
            'items' => [[
                'product_id' => $foreignProduct->id,
                'quantity' => 1,
                'unit_price' => 1500,
            ]],
        ], $branchA->id);
    }

    public function test_sale_item_service_rejects_invalid_payment_method_id(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);
        $product = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'quantity' => 25,
            'reorder_level' => 1,
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $service = app(SaleItemService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Selected payment method is invalid.');

        $service->handleSaveSaleItem([
            'business_branch_id' => $branch->id,
            'customer_id' => null,
            'note' => 'Invalid payment method',
            'paymentStatus' => 'paid',
            'payment_status_id' => 999999,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1500,
            ]],
        ], $branch->id);
    }

    public function test_sale_item_service_saves_a_valid_non_pos_sale(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);
        $product = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'quantity' => 10,
            'reorder_level' => 1,
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        $paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($user);

        $sale = app(SaleItemService::class)->handleSaveSaleItem([
            'business_branch_id' => $branch->id,
            'customer_id' => null,
            'note' => 'Valid sale',
            'paymentStatus' => 'paid',
            'payment_status_id' => $paymentMethod->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1500,
            ]],
        ], $branch->id);

        $this->assertNotNull($sale);
        $this->assertSame('completed', $sale->status);
        $this->assertCount(1, $sale->saleItems);
        $this->assertCount(1, $sale->salePayments);
        $this->assertNotNull($sale->receipt);
        $this->assertDatabaseHas('stock_movements', [
            'business_branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 1,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_non_pos_sale_rejects_duplicate_lines_that_exceed_available_stock(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);
        $product = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'quantity' => 5,
            'reorder_level' => 1,
            'selling_price' => 1500,
            'status' => 'active',
        ]);
        $paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($user);

        try {
            app(SaleItemService::class)->handleSaveSaleItem([
                'business_branch_id' => $branch->id,
                'customer_id' => null,
                'note' => 'Duplicate product lines exceed stock',
                'paymentStatus' => 'paid',
                'payment_status_id' => $paymentMethod->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1500],
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1500],
                ],
            ], $branch->id);

            $this->fail('Expected the sale to be rejected when combined quantity exceeds stock.');
        } catch (\Exception $exception) {
            $this->assertSame(301, $exception->getCode());
        }

        $this->assertSame(5, $product->fresh()->quantity);
        $this->assertSame(0, Sale::where('business_branch_id', $branch->id)->count());
        $this->assertSame(0, StockMovement::where('product_id', $product->id)->count());
    }

    public function test_non_pos_sale_aggregates_duplicate_lines_for_stock_movement(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);
        $product = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'quantity' => 5,
            'reorder_level' => 1,
            'selling_price' => 1500,
            'status' => 'active',
        ]);
        $paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($user);

        $sale = app(SaleItemService::class)->handleSaveSaleItem([
            'business_branch_id' => $branch->id,
            'customer_id' => null,
            'note' => 'Duplicate product lines within stock',
            'paymentStatus' => 'paid',
            'payment_status_id' => $paymentMethod->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1500],
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1500],
            ],
        ], $branch->id);

        $this->assertCount(2, $sale->saleItems);
        $this->assertSame(0, $product->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'type' => 'out',
            'quantity' => 5,
        ]);
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->count());
    }

    public function test_completed_sale_cannot_be_updated(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);
        $product = Product::factory()->create(['business_branch_id' => $branch->id]);

        Sanctum::actingAs($user);

        $sale = Sale::create([
            'business_branch_id' => $branch->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'status' => 'completed',
        ]);

        $this->putJson("/api/sales/branch-sales/{$sale->id}", [
            'business_branch_id' => $branch->id,
            'items' => [[
                'product_id' => $product->id,
                'sale_id' => $sale->id,
                'quantity' => 1,
                'price' => 200,
                'subtotal' => 200,
            ]],
        ])->assertStatus(409);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'status' => 'completed',
            'total_amount' => 100,
        ]);
    }

    public function test_sale_item_service_applies_discount_to_tax_and_subtotal(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);

        $taxCategory = TaxCategory::factory()->create(['business_branch_id' => $branch->id]);
        TaxRate::factory()->create([
            'tax_category_id' => $taxCategory->id,
            'rate' => 0.18,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'tax_category_id' => $taxCategory->id,
            'quantity' => 10,
            'reorder_level' => 1,
            'selling_price' => 1000,
            'is_tax_inclusive' => false,
            'status' => 'active',
        ]);

        $paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($user);

        $sale = app(SaleItemService::class)->handleSaveSaleItem([
            'business_branch_id' => $branch->id,
            'customer_id' => null,
            'note' => 'Discounted sale',
            'paymentStatus' => 'paid',
            'payment_status_id' => $paymentMethod->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 1000,
                'discount' => 200,
            ]],
        ], $branch->id);

        $this->assertSame('1600.00', (string) $sale->subtotal);
        $this->assertSame('288.00', (string) $sale->tax_amount);
        $this->assertSame('1888.00', (string) $sale->total_amount);
        $this->assertSame('200.00', (string) $sale->saleItems->first()->discount);
        $this->assertSame('1600.00', (string) $sale->saleItems->first()->subtotal);
    }

    public function test_non_pos_sale_request_preserves_discount_in_calculated_totals(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);

        $taxCategory = TaxCategory::factory()->create(['business_branch_id' => $branch->id]);
        TaxRate::factory()->create([
            'tax_category_id' => $taxCategory->id,
            'rate' => 0.18,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'tax_category_id' => $taxCategory->id,
            'quantity' => 10,
            'reorder_level' => 1,
            'selling_price' => 1000,
            'is_tax_inclusive' => false,
            'status' => 'active',
        ]);
        $paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/sales/branch-sales', [
            'business_branch_id' => $branch->id,
            'customer_id' => null,
            'status' => 'completed',
            'paymentStatus' => 'paid',
            'payment_status_id' => $paymentMethod->id,
            'currency' => 'UGX',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 1000,
                'discount' => 200,
            ]],
        ])->assertOk();

        $sale = Sale::with(['saleItems', 'salePayments'])->findOrFail($response->json('sale.id'));

        $this->assertSame('1600.00', (string) $sale->subtotal);
        $this->assertSame('288.00', (string) $sale->tax_amount);
        $this->assertSame('1888.00', (string) $sale->total_amount);
        $this->assertSame('200.00', (string) $sale->saleItems->first()->discount);
        $this->assertSame('1888.00', (string) $sale->salePayments->first()->amount);
    }

    public function test_non_pos_sale_request_rejects_invalid_line_discounts(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $user = $this->branchUser($business, $branch);
        $product = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'quantity' => 10,
            'selling_price' => 1000,
            'status' => 'active',
        ]);
        $paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($user);

        foreach ([-1, 1001] as $discount) {
            $this->postJson('/api/sales/branch-sales', [
                'business_branch_id' => $branch->id,
                'customer_id' => null,
                'status' => 'completed',
                'paymentStatus' => 'paid',
                'payment_status_id' => $paymentMethod->id,
                'currency' => 'UGX',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 1000,
                    'discount' => $discount,
                ]],
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('items.0.discount');
        }

        $this->assertSame(0, Sale::where('business_branch_id', $branch->id)->count());
        $this->assertSame(10, $product->fresh()->quantity);
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

    public function test_platform_operators_can_query_business_and_branch_scoped_models(): void
    {
        $businessA = Business::factory()->create();
        $branchA = BusinessBranch::factory()->create(['business_id' => $businessA->id]);
        Role::factory()->create(['business_id' => $businessA->id, 'name' => 'Executive']);
        Product::factory()->create(['business_branch_id' => $branchA->id]);

        $businessB = Business::factory()->create();
        $branchB = BusinessBranch::factory()->create(['business_id' => $businessB->id]);
        Role::factory()->create(['business_id' => $businessB->id, 'name' => 'Operations']);
        Product::factory()->create(['business_branch_id' => $branchB->id]);

        $platformUsers = collect(['CoreSupport', 'siteadmin'])->map(function ($roleName) {
            $role = Role::factory()->create(['business_id' => null, 'name' => $roleName]);

            return User::factory()->create([
                'business_id' => null,
                'business_branch_id' => null,
                'role_id' => $role->id,
            ]);
        });

        $expectedRoleCount = Role::withoutGlobalScopes()->count();
        $expectedProductCount = Product::withoutGlobalScopes()->count();

        foreach ($platformUsers as $platformUser) {
            Sanctum::actingAs($platformUser);

            $this->assertTrue(app(BusinessContext::class)->isPlatformOperator());
            $this->assertSame($expectedRoleCount, Role::count());
            $this->assertSame($expectedProductCount, Product::count());
        }
    }

    public function test_businessless_executive_cannot_query_tenant_models(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        Product::factory()->create(['business_branch_id' => $branch->id]);

        $role = Role::factory()->create(['business_id' => null, 'name' => 'Executive']);
        $executive = User::factory()->create([
            'business_id' => null,
            'business_branch_id' => null,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($executive);

        $this->assertFalse(app(BusinessContext::class)->isPlatformOperator());
        $this->assertSame(0, Role::count());
        $this->assertSame(0, Product::count());
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
