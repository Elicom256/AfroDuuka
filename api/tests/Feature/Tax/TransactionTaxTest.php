<?php

namespace Tests\Feature\Tax;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\TaxCategory;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTaxTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Product $exclusiveProduct;
    protected Product $inclusiveProduct;
    protected Product $untaxedProduct;
    protected TaxCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id]);
        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);

        $this->category = TaxCategory::factory()->create([
            'business_branch_id' => $branch->id,
            'name' => 'VAT',
        ]);
        TaxRate::factory()->create([
            'tax_category_id' => $this->category->id,
            'name' => 'VAT Standard',
            'rate' => 0.18,
            'is_active' => true,
        ]);

        $this->exclusiveProduct = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'tax_category_id' => $this->category->id,
            'is_tax_inclusive' => false,
            'selling_price' => 10_000,
            'quantity' => 50,
            'status' => 'active',
        ]);
        $this->inclusiveProduct = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'tax_category_id' => $this->category->id,
            'is_tax_inclusive' => true,
            'selling_price' => 11_800,
            'quantity' => 50,
            'status' => 'active',
        ]);
        $this->untaxedProduct = Product::factory()->create([
            'business_branch_id' => $branch->id,
            'tax_category_id' => null,
            'is_tax_inclusive' => false,
            'selling_price' => 5_000,
            'quantity' => 50,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->user);
    }

    protected function checkout(array $items, array $payments = []): \Illuminate\Testing\TestResponse
    {
        $total = collect($items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
        $payments = $payments ?: [['method' => 'cash', 'amount' => $total]];

        return $this->postJson('/api/pos/checkout', [
            'items' => $items,
            'payments' => $payments,
            'note' => 'Tax test',
        ]);
    }

    public function test_tax_is_zero_for_untaxed_product(): void
    {
        $response = $this->checkout([
            ['product_id' => $this->untaxedProduct->id, 'quantity' => 2, 'unit_price' => 5_000],
        ]);

        $response->assertStatus(200);
        $saleId = $response->json('sale.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'subtotal' => 10_000,
            'tax_amount' => 0,
            'total_amount' => 10_000,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $this->untaxedProduct->id,
            'tax_rate' => null,
            'tax_amount' => 0,
            'is_tax_inclusive' => false,
        ]);
        $this->assertDatabaseHas('receipts', [
            'sale_id' => $saleId,
            'tax' => 0,
            'total' => 10_000,
        ]);
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'sale',
            'amount' => 10_000,
        ]);
    }

    public function test_tax_is_added_for_tax_exclusive_product(): void
    {
        $response = $this->checkout([
            ['product_id' => $this->exclusiveProduct->id, 'quantity' => 1, 'unit_price' => 10_000],
        ]);

        $response->assertStatus(200);
        $saleId = $response->json('sale.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'subtotal' => 10_000,
            'tax_amount' => 1_800,
            'total_amount' => 11_800,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $this->exclusiveProduct->id,
            'tax_rate' => 0.18,
            'taxable_amount' => 10_000,
            'tax_amount' => 1_800,
            'is_tax_inclusive' => false,
        ]);
        $this->assertDatabaseHas('receipts', [
            'sale_id' => $saleId,
            'subtotal' => 10_000,
            'tax' => 1_800,
            'total' => 11_800,
        ]);
    }

    public function test_tax_is_extracted_for_tax_inclusive_product(): void
    {
        $response = $this->checkout([
            ['product_id' => $this->inclusiveProduct->id, 'quantity' => 1, 'unit_price' => 11_800],
        ]);

        $response->assertStatus(200);
        $saleId = $response->json('sale.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'subtotal' => 10_000,
            'tax_amount' => 1_800,
            'total_amount' => 11_800,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $this->inclusiveProduct->id,
            'tax_rate' => 0.18,
            'taxable_amount' => 10_000,
            'tax_amount' => 1_800,
            'is_tax_inclusive' => true,
        ]);
    }

    public function test_tax_totals_across_mixed_items(): void
    {
        $response = $this->checkout([
            ['product_id' => $this->exclusiveProduct->id, 'quantity' => 1, 'unit_price' => 10_000],
            ['product_id' => $this->untaxedProduct->id, 'quantity' => 2, 'unit_price' => 5_000],
        ]);

        $response->assertStatus(200);
        $saleId = $response->json('sale.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'subtotal' => 20_000,
            'tax_amount' => 1_800,
            'total_amount' => 21_800,
        ]);
    }

    public function test_discount_applies_before_tax(): void
    {
        $response = $this->checkout([
            [
                'product_id' => $this->exclusiveProduct->id,
                'quantity' => 2,
                'unit_price' => 10_000,
                'discount' => 1_000,
            ],
        ], [['method' => 'cash', 'amount' => 21_240]]);

        $response->assertStatus(200);
        $saleId = $response->json('sale.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'subtotal' => 18_000,
            'tax_amount' => 3_240,
            'total_amount' => 21_240,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $this->exclusiveProduct->id,
            'subtotal' => 18_000,
            'taxable_amount' => 18_000,
            'tax_amount' => 3_240,
        ]);
    }

    public function test_inactive_rate_is_not_applied(): void
    {
        $inactiveCategory = TaxCategory::factory()->create([
            'business_branch_id' => $this->exclusiveProduct->business_branch_id,
            'name' => 'Suspended Tax',
        ]);
        TaxRate::factory()->create([
            'tax_category_id' => $inactiveCategory->id,
            'rate' => 0.18,
            'is_active' => false,
        ]);
        $product = Product::factory()->create([
            'business_branch_id' => $this->exclusiveProduct->business_branch_id,
            'tax_category_id' => $inactiveCategory->id,
            'is_tax_inclusive' => false,
            'selling_price' => 10_000,
            'quantity' => 50,
            'status' => 'active',
        ]);

        $response = $this->checkout([
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10_000],
        ]);

        $response->assertStatus(200);
        $saleId = $response->json('sale.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'subtotal' => 10_000,
            'tax_amount' => 0,
            'total_amount' => 10_000,
        ]);
    }

    public function test_held_sale_persists_tax_totals(): void
    {
        $response = $this->postJson('/api/pos/sales/hold', [
            'items' => [
                ['product_id' => $this->exclusiveProduct->id, 'quantity' => 1, 'unit_price' => 10_000],
            ],
            'notes' => 'Hold for tax test',
        ]);

        $response->assertStatus(201);
        $saleId = $response->json('data.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'status' => 'held',
            'subtotal' => 10_000,
            'tax_amount' => 1_800,
            'total_amount' => 11_800,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $this->exclusiveProduct->id,
            'tax_rate' => 0.18,
            'tax_amount' => 1_800,
        ]);
    }

    public function test_pos_product_search_exposes_tax_rate(): void
    {
        $response = $this->getJson('/api/pos/products/search?q=' . urlencode($this->exclusiveProduct->name));

        $response->assertStatus(200);
        $this->assertEquals(
            0.18,
            (float) collect($response->json('data'))->firstWhere('id', $this->exclusiveProduct->id)['tax_rate']
        );
    }
}