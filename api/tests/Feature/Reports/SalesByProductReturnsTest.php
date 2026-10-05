<?php

namespace Tests\Feature\Reports;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Services\Reports\SalesByProductReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Sales by product answers the merchandising question, "did this product actually
 * sell?", so it nets returns against the sale they came from rather than against the
 * period the refund was processed in. That is intentionally different from the
 * financial figures, which never restate a closed period.
 */
class SalesByProductReturnsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $business->id])->id,
        ]);

        $this->actingAs($this->user);
    }

    private function sell(string $productName, int $quantity, float $unitPrice, ?Carbon $at = null): Product
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => $productName,
        ]);

        $total = $unitPrice * $quantity;

        $sale = Sale::create([
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => $total,
            'total_amount' => $total,
        ]);

        if ($at) {
            $sale->forceFill(['created_at' => $at])->save();
        }

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $total,
        ]);

        return $product;
    }

    private function returnUnits(SaleItem $saleItem, int $quantity, float $unitPrice, string $status = 'completed'): void
    {
        $saleReturn = SaleReturn::create([
            'business_branch_id' => $this->branch->id,
            'reason' => 'Customer return',
            'refund_amount' => $unitPrice * $quantity,
            'restock' => true,
            'processed_by' => $this->user->id,
            'status' => $status,
        ]);

        SaleReturnItem::create([
            'sale_return_id' => $saleReturn->id,
            'sale_item_id' => $saleItem->id,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ]);
    }

    private function report(array $filters = ['filter' => 'this_month']): array
    {
        $rows = app(SalesByProductReports::class)->salesByProduct($filters, $this->user->fresh());

        return collect($rows['top_products'])->keyBy('product_name')->all();
    }

    public function test_it_reports_gross_figures_when_nothing_is_returned(): void
    {
        $this->sell('phone', 5, 200000);

        $row = $this->report()['phone'];

        $this->assertEquals(5, $row['quantity_sold']);
        $this->assertEquals(1000000.00, $row['total_revenue']);
        $this->assertEquals(0, $row['returned_quantity']);
    }

    public function test_a_partly_returned_product_reports_net_units_and_revenue(): void
    {
        $product = $this->sell('phone', 5, 200000);
        $saleItem = SaleItem::where('product_id', $product->id)->firstOrFail();

        $this->returnUnits($saleItem, 2, 200000);

        $row = $this->report()['phone'];

        $this->assertEquals(3, $row['quantity_sold'], 'Net units should exclude returned units.');
        $this->assertEquals(600000.00, $row['total_revenue']);
        $this->assertEquals(5, $row['quantity_sold_gross'], 'Gross is still reported for comparison.');
        $this->assertEquals(2, $row['returned_quantity']);
        $this->assertEquals(400000.00, $row['returned_revenue']);
    }

    public function test_a_fully_returned_product_reports_zero(): void
    {
        $product = $this->sell('phone', 2, 200000);
        $saleItem = SaleItem::where('product_id', $product->id)->firstOrFail();

        $this->returnUnits($saleItem, 2, 200000);

        $row = $this->report()['phone'];

        $this->assertEquals(0, $row['quantity_sold']);
        $this->assertEquals(0.00, $row['total_revenue']);
        $this->assertEquals(2, $row['quantity_sold_gross']);
    }

    public function test_draft_and_cancelled_returns_do_not_reduce_the_product(): void
    {
        $product = $this->sell('phone', 2, 200000);
        $saleItem = SaleItem::where('product_id', $product->id)->firstOrFail();

        $this->returnUnits($saleItem, 2, 200000, status: 'draft');
        $this->returnUnits($saleItem, 2, 200000, status: 'cancelled');

        $row = $this->report()['phone'];

        $this->assertEquals(2, $row['quantity_sold']);
        $this->assertEquals(400000.00, $row['total_revenue']);
    }

    public function test_a_return_does_not_reduce_a_product_whose_sale_is_outside_the_window(): void
    {
        $product = $this->sell('historic-phone', 2, 200000, at: Carbon::now()->subMonths(3));
        $saleItem = SaleItem::where('product_id', $product->id)->firstOrFail();

        $this->returnUnits($saleItem, 2, 200000);

        // The sale is not in this period's report, so there is no row to reduce and
        // the return must not leak onto another product either.
        $this->assertArrayNotHasKey('historic-phone', $this->report());
    }

    public function test_a_late_return_still_reduces_its_own_sale_period(): void
    {
        // Sold last month, returned today. Merchandising nets it against the sale, so
        // last month's row does change here even though the financial figures are
        // immutable. That asymmetry is the point of keeping two views.
        $product = $this->sell('phone', 2, 200000, at: Carbon::now()->subMonth()->startOfMonth());
        $saleItem = SaleItem::where('product_id', $product->id)->firstOrFail();

        $this->returnUnits($saleItem, 1, 200000);

        $lastMonth = $this->report(['filter' => 'last_month'])['phone'];

        $this->assertEquals(1, $lastMonth['quantity_sold']);
        $this->assertEquals(200000.00, $lastMonth['total_revenue']);
    }

    public function test_a_return_never_deducts_from_a_different_product(): void
    {
        $phone = $this->sell('phone', 2, 200000);
        $charger = $this->sell('charger', 10, 20000);

        $this->returnUnits(
            SaleItem::where('product_id', $phone->id)->firstOrFail(),
            2,
            200000
        );

        $rows = $this->report();

        $this->assertEquals(0, $rows['phone']['quantity_sold']);
        $this->assertEquals(10, $rows['charger']['quantity_sold']);
        $this->assertEquals(200000.00, $rows['charger']['total_revenue']);
    }

    public function test_ranking_uses_net_revenue_so_a_returned_product_drops_out(): void
    {
        // Sold 2 phones worth 400,000 gross, and 10 chargers worth 200,000.
        $phone = $this->sell('phone', 2, 200000);
        $this->sell('charger', 10, 20000);

        // Phone is top on gross. Fully returning it must promote the charger, and the
        // limit is applied after netting so the phone cannot hold a slot it lost.
        $this->returnUnits(SaleItem::where('product_id', $phone->id)->firstOrFail(), 2, 200000);

        $rows = collect($this->report());

        $this->assertEquals('charger', $rows->first()['product_name']);
        $this->assertEquals(0, ($rows->firstWhere('product_name', 'phone')['quantity_sold'] ?? null));
    }

    public function test_a_return_only_deducts_within_its_own_tenant(): void
    {
        // Tenant A sells two phones and returns one.
        $productA = $this->sell('phone', 2, 200000);
        $this->returnUnits(SaleItem::where('product_id', $productA->id)->firstOrFail(), 1, 200000);

        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);
        $otherUser = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'business_branch_id' => $otherBranch->id,
            'role_id' => Role::factory()->create(['business_id' => $otherBusiness->id])->id,
        ]);

        $this->actingAs($otherUser->fresh());
        $this->branch = $otherBranch;

        // Tenant B sells two phones and returns none, so any deduction showing up
        // here means another tenant's return leaked into this report.
        $productB = $this->sell('phone', 2, 200000);

        $rows = collect(app(SalesByProductReports::class)->salesByProduct(['filter' => 'this_month'], $otherUser->fresh())['top_products']);

        $row = $rows->firstWhere('product_name', 'phone');

        $this->assertCount(1, $rows, 'The report leaked another tenants sale.');
        $this->assertEquals($productB->id, $row['product_id']);
        $this->assertEquals(2, $row['quantity_sold'], 'Another tenants return reduced this tenants report.');
        $this->assertEquals(400000.00, $row['total_revenue']);
        $this->assertEquals(0, $row['returned_quantity']);
    }
}
