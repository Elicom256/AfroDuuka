<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\SaleOrder;
use App\Models\TaxCategory;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;
    protected BusinessBranch $branch;
    protected Role $role;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->role = Role::factory()->create(['business_id' => $this->business->id]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $this->role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    private static int $productCounter = 0;

    private function product(int $quantity = 10, array $overrides = []): Product
    {
        self::$productCounter++;

        return Product::factory()->create(array_merge([
            'business_branch_id' => $this->branch->id,
            'name' => 'Quote Widget ' . self::$productCounter,
            'status' => 'active',
            'is_tax_inclusive' => false,
            'quantity' => $quantity,
        ], $overrides));
    }

    private function createQuote(array $items, array $overrides = []): int
    {
        $response = $this->postJson('/api/quotations', array_merge([
            'customer_id' => null,
            'items' => $items,
        ], $overrides));

        $response->assertStatus(201);

        return $response->json('quotation.id');
    }

    private function createSaleOrderWithAllocation(Product $product, int $quantity): SaleOrder
    {
        $response = $this->postJson('/api/sale-orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => 1000],
            ],
        ]);

        $response->assertStatus(201);

        return SaleOrder::findOrFail($response->json('data.id'));
    }

    public function test_creates_quotation_with_items_and_totals(): void
    {
        $product = $this->product();

        $response = $this->postJson('/api/quotations', [
            'customer_id' => null,
            'valid_until' => now()->addDays(7)->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 5000],
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 3000],
            ],
            'notes' => 'Initial quote',
            'terms' => 'Valid for 7 days',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('quotation.status', 'draft')
            ->assertJsonPath('quotation.subtotal', '13000.00')
            ->assertJsonPath('quotation.tax_amount', '0.00')
            ->assertJsonPath('quotation.total_amount', '13000.00')
            ->assertJsonPath('quotation.currency', 'UGX');

        $quotationId = $response->json('quotation.id');

        $this->assertMatchesRegularExpression('/^QT-\d{8}-\d{4}$/', $response->json('quotation.quotation_number'));

        $this->assertDatabaseHas('quotations', [
            'id' => $quotationId,
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'draft',
            'total_amount' => '13000.00',
        ]);

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotationId,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => '5000.00',
            'subtotal' => '10000.00',
        ]);
    }

    public function test_quotation_totals_match_tax_service(): void
    {
        $taxCategory = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);
        TaxRate::factory()->create([
            'tax_category_id' => $taxCategory->id,
            'rate' => '0.18',
            'is_active' => true,
        ]);

        $product = $this->product(10, [
            'tax_category_id' => $taxCategory->id,
            'is_tax_inclusive' => false,
        ]);

        $tax = (new TaxService)->calculateForProduct($product, 5000, 2, 0);

        $response = $this->postJson('/api/quotations', [
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 5000]],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('quotation.subtotal', sprintf("%.2f", $tax['taxable_amount']))
            ->assertJsonPath('quotation.tax_amount', sprintf("%.2f", $tax['tax_amount']))
            ->assertJsonPath('quotation.total_amount', sprintf("%.2f", $tax['taxable_amount'] + $tax['tax_amount']));
    }

    public function test_quotation_requires_at_least_one_item(): void
    {
        $this->postJson('/api/quotations', ['items' => []])
            ->assertStatus(422);
    }

    public function test_quotation_rejects_dangling_product(): void
    {
        $this->postJson('/api/quotations', [
            'items' => [['product_id' => 999999, 'quantity' => 1, 'unit_price' => 100]],
        ])->assertStatus(422);
    }

    public function test_index_filters_by_status(): void
    {
        $first = $this->createQuote([
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        Quotation::findOrFail($first)->update(['status' => 'sent']);
        $second = $this->createQuote([
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $sent = $this->getJson('/api/quotations?status=sent');
        $sent->assertOk()
            ->assertJsonCount(1, 'quotations.data')
            ->assertJsonPath('quotations.data.0.id', $first);

        $draft = $this->getJson('/api/quotations?status=draft');
        $draft->assertOk()
            ->assertJsonCount(1, 'quotations.data')
            ->assertJsonPath('quotations.data.0.id', $second);
    }

    public function test_index_expired_filter(): void
    {
        $expired = $this->createQuote(
            [['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000]],
            ['valid_until' => now()->subDay()->toDateString()]
        );

        $active = $this->createQuote(
            [['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000]],
            ['valid_until' => now()->addWeek()->toDateString()]
        );

        $expiredList = $this->getJson('/api/quotations?status=expired');
        $expiredList->assertOk();

        $ids = collect($expiredList->json('quotations.data'))->pluck('id')->all();
        $this->assertContains($expired, $ids);
        $this->assertNotContains($active, $ids);
    }

    public function test_update_replaces_items_and_recomputes_totals(): void
    {
        $product = $this->product();

        $quotationId = $this->createQuote([
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 5000],
        ]);

        $response = $this->putJson("/api/quotations/{$quotationId}", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 2000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('quotation.total_amount', '6000.00');

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotationId,
            'quantity' => 3,
            'subtotal' => '6000.00',
        ]);

        $this->assertSame(1, DB::table('quotation_items')->where('quotation_id', $quotationId)->count());
    }

    public function test_send_transitions_quote_to_sent(): void
    {
        $quotationId = $this->createQuote([
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->postJson("/api/quotations/{$quotationId}/send")
            ->assertStatus(200)
            ->assertJsonPath('quotation.status', 'sent');

        $this->postJson("/api/quotations/{$quotationId}/send")
            ->assertStatus(409);
    }

    public function test_update_rejected_after_accept(): void
    {
        $quotationId = $this->createQuote([
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->postJson("/api/quotations/{$quotationId}/accept")->assertOk();

        $this->putJson("/api/quotations/{$quotationId}", [
            'items' => [['product_id' => $this->product()->id, 'quantity' => 5, 'unit_price' => 1000]],
        ])->assertStatus(409);
    }

    public function test_accept_creates_sale_order_reserves_inventory_without_financial_records(): void
    {
        $product = $this->product(10);
        $quotationId = $this->createQuote([
            ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 4000],
        ]);

        $response = $this->postJson("/api/quotations/{$quotationId}/accept");

        $response->assertOk()
            ->assertJsonPath('quotation.status', 'accepted')
            ->assertJsonPath('sale_order.status', 'approved');

        $quotation = Quotation::findOrFail($quotationId);
        $this->assertNotNull($quotation->accepted_order_id);

        $this->assertDatabaseHas('sale_orders', [
            'id' => $quotation->accepted_order_id,
            'business_id' => $this->business->id,
            'quotation_id' => $quotationId,
            'status' => 'approved',
            'total_amount' => '12000.00',
        ]);

        $this->assertDatabaseHas('sale_order_items', [
            'sale_order_id' => $quotation->accepted_order_id,
            'product_id' => $product->id,
            'quantity' => 3,
            'allocated_qty' => 3,
        ]);

        $this->assertSame(10, $product->refresh()->quantity);
        $this->assertSame(7, $product->availableQuantity());

        $this->assertMatchesRegularExpression('/^SO-\d{8}-\d{4}$/', $response->json('sale_order.order_number'));

        foreach (['sales', 'receipts', 'cash_flows', 'sale_payments'] as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
    }

    public function test_accept_is_idempotent(): void
    {
        $quotationId = $this->createQuote([
            ['product_id' => $this->product(10)->id, 'quantity' => 2, 'unit_price' => 1000],
        ]);

        $this->postJson("/api/quotations/{$quotationId}/accept")->assertOk();
        $this->postJson("/api/quotations/{$quotationId}/accept")->assertStatus(409);

        $this->assertSame(1, SaleOrder::where('business_id', $this->business->id)->count());
    }

    public function test_create_rejects_allocation_overrun(): void
    {
        $product = $this->product(10);
        $this->createSaleOrderWithAllocation($product, 8);

        $this->postJson('/api/quotations', [
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000]],
        ])->assertStatus(422);
    }

    public function test_cancelled_sale_order_releases_allocation(): void
    {
        $product = $this->product(5);
        $order = $this->createSaleOrderWithAllocation($product, 5);

        $this->assertSame(0, $product->availableQuantity());

        $this->putJson("/api/sale-orders/{$order->id}", ['status' => 'cancelled'])
            ->assertStatus(200);

        $quotationId = $this->createQuote([
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 1000],
        ]);

        $this->postJson("/api/quotations/{$quotationId}/accept")->assertOk();
    }

    public function test_user_cannot_access_another_business_quotation(): void
    {
        $quotationId = $this->createQuote([
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);
        $otherRole = Role::factory()->create(['business_id' => $otherBusiness->id]);
        $otherUser = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'business_branch_id' => $otherBranch->id,
            'role_id' => $otherRole->id,
        ]);

        Sanctum::actingAs($otherUser);

        $this->getJson("/api/quotations/{$quotationId}")
            ->assertStatus(404);
    }

    public function test_pdf_is_base64_for_json_accept(): void
    {
        $quotationId = $this->createQuote([
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $response = $this->getJson("/api/quotations/{$quotationId}/pdf");

        $response->assertOk();

        $this->assertStringStartsWith('quotation-QT', $response->json('filename'));
        $this->assertStringEndsWith('.pdf', $response->json('filename'));

        $pdf = base64_decode($response->json('pdf'));
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_pdf_downloads_for_direct_request(): void
    {
        $quotationId = $this->createQuote([
            ['product_id' => $this->product()->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->get("/api/quotations/{$quotationId}/pdf")
            ->assertStatus(200)
            ->assertHeader('content-disposition');
    }

    public function test_destroy_allowed_for_draft_and_sent_but_not_accepted(): void
    {
        $quotationId = $this->createQuote([
            ['product_id' => $this->product(10)->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->deleteJson("/api/quotations/{$quotationId}")->assertOk();
        $this->assertDatabaseMissing('quotations', ['id' => $quotationId]);
        $this->assertSame(0, DB::table('quotation_items')->count());

        $acceptedId = $this->createQuote([
            ['product_id' => $this->product(10)->id, 'quantity' => 1, 'unit_price' => 1000],
        ]);

        $this->postJson("/api/quotations/{$acceptedId}/accept")->assertOk();
        $this->deleteJson("/api/quotations/{$acceptedId}")->assertStatus(409);
    }
}