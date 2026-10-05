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
use Illuminate\Support\Carbon;
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
     * @return array<string, SaleItem>
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
    private function recordReturn(
        array $saleItems,
        int $quantityEach = 1,
        string $status = 'completed',
        ?Carbon $processedAt = null
    ): SaleReturn {
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

        if ($processedAt) {
            $saleReturn->forceFill(['created_at' => $processedAt])->save();
        }

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
            'transaction_date' => ($processedAt ?? now())->toDateString(),
            'created_by' => $this->user->id,
        ]);

        return $saleReturn->refresh();
    }

    protected int $historicSaleCount = 0;

    /**
     * Builds a sale dated outside a last_30_days window, to exercise returns that
     * land in one period against a sale from an earlier one.
     */
    private function saleDatedDaysAgo(int $days): array
    {
        $name = 'historic-phone-'.(++$this->historicSaleCount);
        $items = $this->completedSaleOfPhones([$name => 1]);
        $saleItem = array_values($items)[0];
        $at = Carbon::now()->subDays($days);

        $saleItem->sale->forceFill(['created_at' => $at])->save();

        // The sale's inflow has to move with it, otherwise the cashflow side of the
        // reconciliation sits in the window while the sale it came from does not.
        CashFlow::where('sale_id', $saleItem->sale_id)
            ->update(['transaction_date' => $at->toDateString()]);

        return [$name => $saleItem];
    }

    protected int $datedSaleCount = 0;

    /**
     * Builds a completed sale pinned to an exact timestamp, so month boundaries can be
     * tested without depending on which day of the month the suite happens to run.
     */
    private function saleDatedAt(Carbon $at, int $quantity = 1): SaleItem
    {
        $name = 'dated-phone-'.(++$this->datedSaleCount);
        $items = $this->completedSaleOfPhones([$name => $quantity]);
        $saleItem = array_values($items)[0];

        $saleItem->sale->forceFill(['created_at' => $at])->save();
        CashFlow::where('sale_id', $saleItem->sale_id)
            ->update(['transaction_date' => $at->toDateString()]);

        return $saleItem;
    }

    /**
     * The month filters used to be derived from a day count anchored on today, which
     * made "last month" a rolling window rather than a calendar month: partway through
     * a month it reached back too far and reached forward into the current one. These
     * pin the calendar edges so the closed-period rule below is actually testable.
     */
    public function test_last_month_covers_the_whole_calendar_month_and_nothing_after(): void
    {
        // Two last-month sales, one near each edge of the month, plus two today.
        // A sliding window would drop the early one and admit both of today's, which
        // happens to be the same count of two, so the counts alone are not enough to
        // catch the bug; the money and the transaction count together are.
        $this->saleDatedAt(Carbon::now()->subMonth()->startOfMonth()->addHours(10));
        $this->saleDatedAt(Carbon::now()->subMonth()->endOfMonth()->subHours(10));
        $this->saleDatedAt(Carbon::now());
        $this->saleDatedAt(Carbon::now());

        $analytics = app(SaleItemService::class)->analytics('last_month');

        $this->assertEquals(400000.0, $analytics['gross_sales'], 'Only the two last-month sales belong here.');
        $this->assertEquals(2, $analytics['total_transactions']);
        $this->assertEquals(2, $analytics['items_sold']);
        $this->assertEquals(400000.0, $analytics['total_sales'], 'No returns were recorded, so net is gross.');
    }

    public function test_this_month_starts_on_the_first_of_the_month(): void
    {
        $this->saleDatedAt(Carbon::now()->subMonth()->startOfMonth()->addHours(10));
        $this->saleDatedAt(Carbon::now()->subMonth()->endOfMonth()->subHours(10));
        $this->saleDatedAt(Carbon::now());

        $analytics = app(SaleItemService::class)->analytics('this_month');

        $this->assertEquals(200000.0, $analytics['gross_sales'], 'Last months sales must not appear in this month.');
        $this->assertEquals(1, $analytics['total_transactions']);
    }

    /**
     * The requirement in the user's own words: last month's sales must not change
     * because something was returned today. Both sides of the window matter here,
     * because the refund is processed inside this month and the sale sits in last
     * month, so a sliding "last month" window would put them in the same report.
     */
    public function test_a_return_processed_this_month_does_not_reduce_last_month(): void
    {
        $saleItem = $this->saleDatedAt(Carbon::now()->subMonth()->startOfMonth()->addHours(10));

        $before = app(SaleItemService::class)->analytics('last_month');
        $this->assertEquals(200000.0, $before['gross_sales']);
        $this->assertEquals(200000.0, $before['total_sales']);

        $this->recordReturn([$saleItem]);

        $after = app(SaleItemService::class)->analytics('last_month');

        $this->assertEquals(200000.0, $after['gross_sales']);
        $this->assertEquals(0.0, $after['sales_returns'], 'The refund is this months contra-revenue, not last months.');
        $this->assertEquals(200000.0, $after['total_sales'], 'Last month is unchanged.');
        $this->assertEquals(1, $after['items_sold'], 'Units are unaffected by a return from another period.');

        // ...and the refund is reported where it actually happened.
        $thisMonth = app(SaleItemService::class)->analytics('this_month');
        $this->assertEquals(0.0, $thisMonth['gross_sales']);
        $this->assertEquals(200000.0, $thisMonth['sales_returns']);
        $this->assertEquals(-200000.0, $thisMonth['total_sales']);

        $returnsThisMonth = app(SaleItemService::class)->returnAnalytics('this_month');
        $this->assertEquals(200000.0, $returnsThisMonth['sales_returns']);
        $this->assertEquals(200000.0, $returnsThisMonth['returned_revenue']);
        $this->assertEquals(-200000.0, $returnsThisMonth['net_sales']);
    }

    public function test_monthly_trends_cover_every_day_of_the_month(): void
    {
        $this->saleDatedAt(Carbon::now()->subMonth()->startOfMonth()->addHours(10));
        $this->recordReturn([$this->saleDatedAt(Carbon::now()->subMonth()->startOfMonth()->addHours(12))]);

        $analytics = app(SaleItemService::class)->analytics('last_month');
        $expectedDays = Carbon::now()->subMonth()->daysInMonth;

        $this->assertCount(
            $expectedDays,
            $analytics['sales_trend'],
            'A monthly trend needs one point per day of that month.'
        );

        $labels = array_column($analytics['sales_trend'], 'date');
        $this->assertEquals('Sep 01', $labels[0], 'The trend must open on the first of the month.');
        $this->assertEquals(Carbon::now()->subMonth()->endOfMonth()->format('M d'), end($labels));

        // The trend is what the chart draws, so it still has to reconcile with the
        // totals printed beside it.
        $this->assertEqualsWithDelta(
            $analytics['total_sales'],
            array_sum(array_column($analytics['sales_trend'], 'amount')),
            0.001
        );
    }

    private function analytics(): array
    {
        return app(SaleItemService::class)->analytics('last_30_days');
    }

    private function returnAnalytics(): array
    {
        return app(SaleItemService::class)->returnAnalytics('last_30_days');
    }

    /**
     * Net money over the same last_30_days window the sales analytics use, so the
     * two sides are compared on the same period.
     */
    private function cashflowNetInWindow(): float
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $in = (float) CashFlow::whereIn('type', ['sale', 'payment_in'])
            ->where('transaction_date', '>=', $start->toDateString())
            ->sum('amount');

        $out = (float) CashFlow::where('type', 'refund')
            ->where('transaction_date', '>=', $start->toDateString())
            ->sum('amount');

        return $in - $out;
    }

    /**
     * The accounting rule this whole file ultimately rests on: a period that has
     * already been reported must stay exactly as it was reported. A return
     * processed today against a sale from before the window is a contra-revenue
     * of today, and must not rewrite the earlier period's figures.
     */
    public function test_a_return_today_does_not_rewrite_the_earlier_period(): void
    {
        $items = $this->saleDatedDaysAgo(40);

        $before = $this->analytics();
        $this->assertEquals(0.0, $before['gross_sales'], 'Historic sale should be outside the window.');

        // Return the 40-day-old sale today.
        $this->recordReturn([array_values($items)[0]]);

        $after = $this->analytics();

        $this->assertEquals(0.0, $after['gross_sales']);
        $this->assertEquals(200000.0, $after['sales_returns'], 'The refund belongs to the period it was processed in.');
        $this->assertEquals(-200000.0, $after['total_sales'], 'Net may go negative; it is not clamped.');
    }

    public function test_units_are_not_reduced_by_returns_of_sales_from_another_period(): void
    {
        $items = $this->saleDatedDaysAgo(40);

        $this->assertEquals(0, $this->analytics()['items_sold']);

        $this->recordReturn([array_values($items)[0]]);

        $analytics = $this->analytics();

        // This period sold nothing, so it cannot have had units returned from it.
        $this->assertEquals(0, $analytics['items_sold']);
        $this->assertEquals(1, $analytics['returned_quantity'], 'The returned unit is still reported, just not netted off.');
    }

    public function test_cashflow_and_sales_analytics_still_agree_across_periods(): void
    {
        $items = $this->saleDatedDaysAgo(40);
        $this->recordReturn([array_values($items)[0]]);

        $this->assertEquals(
            (float) $this->analytics()['total_sales'],
            $this->cashflowNetInWindow()
        );
    }

    public function test_return_rate_is_null_when_the_period_has_no_sales(): void
    {
        $items = $this->saleDatedDaysAgo(40);
        $this->recordReturn([array_values($items)[0]]);

        $returns = $this->returnAnalytics();

        $this->assertEquals(0.0, $returns['gross_sales']);
        $this->assertEquals(200000.0, $returns['sales_returns'], 'The return is still visible in the returns report.');
        $this->assertNull(
            $returns['return_rate'],
            'Dividing by zero sales must report undefined, not 0%.'
        );
    }

    public function test_same_period_return_is_still_deducted_in_that_period(): void
    {
        $items = $this->completedSaleOfPhones();
        $this->recordReturn([$items['phone-a']]);

        $analytics = $this->analytics();

        $this->assertEquals(200000.0, $analytics['sales_returns']);
        $this->assertEquals(200000.0, $analytics['total_sales']);
        $this->assertEquals(1, $analytics['items_sold']);
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

        // The finance surfaces read cash_flows directly, the sales surfaces read
        // sales + sale_return_items. They must not disagree.
        $this->assertEquals(
            (float) $this->analytics()['total_sales'],
            $this->cashflowNetInWindow(),
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
