<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Services\SaleItemService;
use App\Services\SaleReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A sale return never writes to sales or sale_items; it is recorded in
 * sale_returns/sale_return_items and mirrored as a cash_flows row of type
 * 'refund'. Revenue reporting therefore has to reconcile the two, and every
 * revenue surface has to agree. These tests pin that behaviour using the
 * worked example in today.md: two phones at 200,000 each, 400,000 gross.
 */
class SaleReturnRevenueReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected BusinessBranch $branch;

    protected const PHONE_PRICE = 200000.00;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id]);
        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Builds a completed sale of $quantity phones at PHONE_PRICE and returns the
     * created sale items keyed by product name so a return can target one.
     *
     * @return array<string, \App\Models\SaleItem>
     */
    private function completedSaleOfPhones(array $quantitiesByProduct = ['phone-a' => 1, 'phone-b' => 1]): array
    {
        $sale = Sale::create([
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
            'subtotal' => 0,
            'total_amount' => 0,
        ]);

        $total = 0;
        $items = [];

        foreach ($quantitiesByProduct as $name => $quantity) {
            $product = Product::factory()->create([
                'business_branch_id' => $this->branch->id,
                'name' => $name,
            ]);

            $subtotal = self::PHONE_PRICE * $quantity;

            $items[$name] = SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => self::PHONE_PRICE,
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;
        }

        $sale->update(['subtotal' => $total, 'total_amount' => $total]);

        // Mirrors the inflow SaleService records, so the cashflow-derived and
        // sales-derived views of revenue are reconcilable in these tests.
        CashFlow::create([
            'transaction_code' => 'CF-S-'.str_pad($sale->id, 6, '0', STR_PAD_LEFT),
            'type' => 'sale',
            'amount' => $total,
            'currency' => 'UGX',
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'sale_id' => $sale->id,
            'category' => 'product_sales',
            'payment_method' => 'cash',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        return $items;
    }

    /**
     * Records a return directly through the same rows SaleReturnService writes:
     * a sale_returns header, sale_return_items lines, and the 'refund' cash_flows
     * row that CashFlowService::createCashFlowForSaleReturn() emits.
     */
    private function recordReturn(array $saleItems, int $quantityEach = 1, string $status = 'completed'): SaleReturn
    {
        $refund = 0;

        $saleReturn = SaleReturn::create([
            'business_branch_id' => $this->branch->id,
            'reason' => 'Customer return',
            'refund_amount' => 0,
            'restock' => true,
            'processed_by' => $this->user->id,
            'status' => $status,
        ]);

        foreach ($saleItems as $saleItem) {
            $subtotal = self::PHONE_PRICE * $quantityEach;

            SaleReturnItem::create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => $saleItem->id,
                'quantity' => $quantityEach,
                'subtotal' => $subtotal,
            ]);

            $refund += $subtotal;
        }

        $saleReturn->update(['refund_amount' => $refund]);

        CashFlow::create([
            'transaction_code' => 'CF-SR-'.str_pad($saleReturn->id, 6, '0', STR_PAD_LEFT),
            'type' => 'refund',
            'amount' => $refund,
            'currency' => 'UGX',
            'business_id' => $this->user->business_id,
            'business_branch_id' => $this->branch->id,
            'sale_return_id' => $saleReturn->id,
            'category' => 'product_sales',
            'payment_method' => 'cash',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        return $saleReturn->refresh();
    }

    private function analytics(): array
    {
        return app(SaleItemService::class)->analytics('last_30_days');
    }

    public function test_gross_revenue_is_untouched_by_returns(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']]);

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['gross_sales']);
    }

    public function test_partial_return_deducts_only_the_returned_portion(): void
    {
        $items = $this->completedSaleOfPhones();

        // today.md: one phone of the two returned leaves 200,000 expected revenue.
        $this->recordReturn([$items['phone-a']]);

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['gross_sales']);
        $this->assertEquals(200000.00, $analytics['sales_returns']);
        $this->assertEquals(200000.00, $analytics['total_sales']);
    }

    public function test_full_return_reduces_revenue_to_zero(): void
    {
        $items = $this->completedSaleOfPhones();

        // today.md: both phones returned leaves 0 expected revenue.
        $this->recordReturn([$items['phone-a'], $items['phone-b']]);

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['gross_sales']);
        $this->assertEquals(400000.00, $analytics['sales_returns']);
        $this->assertEquals(0.00, $analytics['total_sales']);
    }

    public function test_items_sold_is_deducted_by_returned_quantity(): void
    {
        $items = $this->completedSaleOfPhones();

        $this->assertEquals(2, $this->analytics()['items_sold']);

        $this->recordReturn([$items['phone-a']]);

        $analytics = $this->analytics();

        $this->assertEquals(1, $analytics['returned_quantity']);
        $this->assertEquals(1, $analytics['items_sold']);
    }

    public function test_partial_quantity_return_deducts_only_that_quantity(): void
    {
        $items = $this->completedSaleOfPhones(['phone-a' => 5]);

        $this->assertEquals(5, $this->analytics()['items_sold']);

        $this->recordReturn([$items['phone-a']], quantityEach: 2);

        $analytics = $this->analytics();

        $this->assertEquals(2, $analytics['returned_quantity']);
        $this->assertEquals(3, $analytics['items_sold']);
        $this->assertEquals(1000000.00, $analytics['gross_sales']);
        $this->assertEquals(400000.00, $analytics['sales_returns']);
        $this->assertEquals(600000.00, $analytics['total_sales']);
    }

    public function test_deleting_the_return_restores_revenue(): void
    {
        $items = $this->completedSaleOfPhones();
        $saleReturn = $this->recordReturn([$items['phone-a']]);

        $this->assertEquals(200000.00, $this->analytics()['total_sales']);

        $saleReturn->saleReturnItems()->delete();
        $saleReturn->delete();
        CashFlow::where('sale_return_id', $saleReturn->id)->delete();

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['total_sales']);
        $this->assertEquals(0.00, $analytics['sales_returns']);
        $this->assertEquals(2, $analytics['items_sold']);
    }

    public function test_returns_do_not_reduce_another_businesss_revenue(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']]);

        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);
        $otherUser = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'business_branch_id' => $otherBranch->id,
            'role_id' => Role::factory()->create(['business_id' => $otherBusiness->id])->id,
        ]);

        // BaseModel stamps business_id from the authenticated user on create, so
        // switch tenants before writing the second business' rows.
        $this->actingAs($otherUser->fresh());

        $otherSale = Sale::create([
            'business_branch_id' => $otherBranch->id,
            'user_id' => $otherUser->id,
            'status' => 'completed',
            'subtotal' => 750000.00,
            'total_amount' => 750000.00,
        ]);

        SaleItem::create([
            'sale_id' => $otherSale->id,
            'product_id' => Product::factory()->create([
                'business_branch_id' => $otherBranch->id,
            ])->id,
            'quantity' => 3,
            'unit_price' => 250000.00,
            'subtotal' => 750000.00,
        ]);

        $analytics = $this->analytics();

        $this->assertEquals(750000.00, $analytics['gross_sales']);
        $this->assertEquals(0.00, $analytics['sales_returns']);
        $this->assertEquals(750000.00, $analytics['total_sales']);
    }

    public function test_cashflow_and_sales_analytics_agree_on_net_revenue(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']]);

        $salesAnalytics = $this->analytics();

        // The finance surfaces read cash_flows directly, the sales surfaces read
        // sales + sale_return_items. They must not disagree.
        $cashFlowNet = CashFlow::whereIn('type', ['sale', 'payment_in'])->sum('amount')
            - CashFlow::where('type', 'refund')->sum('amount');

        $this->assertEquals(
            (float) $salesAnalytics['total_sales'],
            (float) $cashFlowNet,
            'Sales analytics and cashflow net revenue diverged after a return.'
        );
    }

    public function test_return_rate_is_reported_against_gross_sales(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']]);

        $returns = app(SaleItemService::class)->returnAnalytics('last_30_days');

        $this->assertEquals(400000.00, $returns['gross_sales']);
        $this->assertEquals(200000.00, $returns['sales_returns']);
        $this->assertEquals(200000.00, $returns['net_sales']);
        $this->assertEquals(50.0, $returns['return_rate']);
    }

    public function test_trend_is_net_of_returns_so_it_agrees_with_the_totals(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']]);

        $analytics = $this->analytics();

        $today = now()->format('M d');
        $todayPoint = collect($analytics['sales_trend'])->firstWhere('date', $today);

        $this->assertNotNull($todayPoint, 'Trend is missing a point for today.');
        $this->assertEquals(
            (float) $analytics['total_sales'],
            (float) $todayPoint['amount'],
            'Trend amount disagrees with the net total it is supposed to sum to.'
        );
        $this->assertEquals(200000.0, (float) $todayPoint['amount']);
        $this->assertEquals(1, $todayPoint['items']);
    }

    public function test_trend_totals_sum_to_the_net_total(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']]);

        $analytics = $this->analytics();

        $this->assertEquals(
            (float) $analytics['total_sales'],
            round((float) collect($analytics['sales_trend'])->sum('amount'), 2),
            'Summed trend points do not equal the reported net total.'
        );
        $this->assertEquals(
            $analytics['items_sold'],
            (int) collect($analytics['sales_trend'])->sum('items')
        );
    }

    public function test_draft_returns_do_not_reduce_revenue(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']], status: 'draft');

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['total_sales']);
        $this->assertEquals(0.00, $analytics['sales_returns']);
        $this->assertEquals(2, $analytics['items_sold']);
    }

    public function test_cancelled_returns_do_not_reduce_revenue(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']], status: 'cancelled');

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['total_sales']);
        $this->assertEquals(0.00, $analytics['sales_returns']);
        $this->assertEquals(2, $analytics['items_sold']);
    }

    public function test_editing_a_return_replaces_its_items_instead_of_appending_them(): void
    {
        $items = $this->completedSaleOfPhones();
        $saleReturn = $this->recordReturn([$items['phone-a']]);

        $this->assertEquals(200000.00, $this->analytics()['total_sales']);

        // Widen the existing return from one phone to both phones.
        app(SaleReturnService::class)->handleUpdateSaleReturn($saleReturn, [
            'reason' => 'Customer return',
            'restock' => true,
            'items' => [
                ['sale_item_id' => $items['phone-a']->id, 'quantity' => 1],
                ['sale_item_id' => $items['phone-b']->id, 'quantity' => 1],
            ],
        ]);

        // Two lines total, not the original one plus two replacements.
        $this->assertEquals(
            2,
            SaleReturnItem::where('sale_return_id', $saleReturn->id)->count(),
            'Editing a return left the superseded return items behind.'
        );

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['sales_returns']);
        $this->assertEquals(0.00, $analytics['total_sales']);
        $this->assertEquals(0, $analytics['items_sold']);
    }

    public function test_shrinking_a_return_gives_the_revenue_back(): void
    {
        $items = $this->completedSaleOfPhones();
        $saleReturn = $this->recordReturn([$items['phone-a'], $items['phone-b']]);

        $this->assertEquals(0.00, $this->analytics()['total_sales']);

        app(SaleReturnService::class)->handleUpdateSaleReturn($saleReturn, [
            'reason' => 'Customer return',
            'restock' => true,
            'items' => [
                ['sale_item_id' => $items['phone-a']->id, 'quantity' => 1],
            ],
        ]);

        $analytics = $this->analytics();

        $this->assertEquals(200000.00, $analytics['sales_returns']);
        $this->assertEquals(200000.00, $analytics['total_sales']);
        $this->assertEquals(1, $analytics['items_sold']);
    }

    public function test_deleting_a_return_after_an_edit_still_leaves_no_stale_items(): void
    {
        $items = $this->completedSaleOfPhones();
        $saleReturn = $this->recordReturn([$items['phone-a']]);

        app(SaleReturnService::class)->handleUpdateSaleReturn($saleReturn, [
            'reason' => 'Customer return',
            'restock' => true,
            'items' => [['sale_item_id' => $items['phone-a']->id, 'quantity' => 1]],
        ]);

        app(SaleReturnService::class)->handleDeleteSaleReturn($saleReturn->fresh());

        $this->assertEquals(0, SaleReturnItem::where('sale_return_id', $saleReturn->id)->count());

        $analytics = $this->analytics();

        $this->assertEquals(400000.00, $analytics['total_sales']);
        $this->assertEquals(0.00, $analytics['sales_returns']);
        $this->assertEquals(2, $analytics['items_sold']);
    }
}
