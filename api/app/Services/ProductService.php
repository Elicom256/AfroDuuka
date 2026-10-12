<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;

class ProductService
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    /**
     * Inventory figures for the analytics cards.
     *
     * This used to also return a `topProducts` list ranking products by realised profit.
     * The query behind it was `SUM(quantity * (selling_price - cost_price))` evaluated
     * over sale_items, and sale_items has neither column -- they live on products -- so
     * Postgres raised 42703, inventoryAnalytics() caught it and answered 500, and the
     * sixth card on the executive analytics page showed its error state. The same query
     * backed the Operations analytics page.
     *
     * It is removed rather than repaired because no consumer read it: the two components
     * that call this endpoint read statusBreakdown, lowStock, outOfStock and the totals,
     * and the top-products components in the app all read a `top_products` key from
     * different endpoints.
     *
     * Repairing it was not an option either. sale_items stores no cost, so a profit
     * figure per sale can only be derived from the product's *current* cost_price, which
     * is not the cost the sale was made at. A correct answer needs a cost-at-sale column,
     * which is a schema decision rather than a bug fix.
     */
    public function analytics()
    {
        $products = Product::query();

        $totalProducts = (clone $products)->count();

        $totalInventoryValue = (clone $products)
            ->selectRaw('SUM(quantity * cost_price) as total')
            ->first()
            ->total;
        $totalPotentialRevenue = (clone $products)
            ->where('quantity', '>', 0)
            ->selectRaw('COALESCE(SUM(quantity * selling_price), 0) as total')
            ->first()
            ->total;

        $profits = (clone $products)
            ->where('quantity', '>', 0)
            ->selectRaw('COALESCE(SUM((selling_price - cost_price) * quantity), 0) as total')
            ->first()
            ->total;

        $lowStock = (clone $products)
            ->where('quantity', '>=', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();

        $outOfStock = (clone $products)
            ->where('quantity', '<=', 0)
            ->count();

        $statusBreakdown = (clone $products)
            ->selectRaw('status, COUNT(*) as totalByStatus')
            ->groupBy('status')
            ->get();

        // A count, where this used to be every matching product hydrated in full.
        // InventoryAnalytics.tsx renders `analytics.slowMoving?.length` and nothing
        // else, so the rows behind that number were crossing the wire, occupying memory
        // and being serialised, in order to have their length taken. Two of the three
        // predicates here also ran against a whole-table read with no ceiling.
        $slowMovingCount = (clone $products)
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('last_sold_at')
                    ->orWhere('last_sold_at', '<=', now()->subDays(30));
            })
            ->count();

        // deadStock, fastMoving and poorMarginProducts are gone.
        //
        // They were each a full hydration — and poorMarginProducts was documented as the
        // slowest statement in the set — shipped to a client that reads none of them.
        // The only consumer of this endpoint reads statusBreakdown, lowStock,
        // outOfStock, the totals, and slowMoving. Dead stock is not lost with its
        // removal: DeadStockReports serves a dedicated endpoint for it, and the report
        // page reads that instead.
        //
        // This is the same call already taken for topProducts above, for the same
        // reason. InventoryAnalyticsTest asserted these keys existed; that assertion was
        // pinning the response shape rather than any behaviour, and is updated with it.

        return [
            'total_products' => $totalProducts,
            'totalInventoryValue' => $totalInventoryValue,
            'totalPotentialRevenue' => $totalPotentialRevenue,
            'totalExpectedProfit' => $profits,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'statusBreakdown' => $statusBreakdown,
            'slowMoving' => $slowMovingCount,
        ];
    }

    public function productPerformance(Product $product, string $period = 'last_7_days')
    {
        $dates = $this->analyticsTrendHelper->getPeriodDates($period);
        $date_range = [$dates['start'], $dates['end']];

        $sales = SaleItem::where('product_id', $product->id)
            ->whereBetween('created_at', $date_range)
            ->sum('subtotal');

        $purchases = PurchaseItem::where('product_id', $product->id)
            ->whereBetween('created_at', $date_range)
            ->sum('subtotal');

        $gpm = 0;
        if ($sales > 0) {
            $gpm = (($sales - $purchases) / $sales) * 100;
        }

        $currentMonthSales = SaleItem::where('product_id', $product->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('subtotal');

        $lastMonthSales = SaleItem::where('product_id', $product->id)
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->sum('subtotal');

        $salesGrowth = 0;
        $growthLabel = null;
        if ($lastMonthSales == 0 && $currentMonthSales > 0) {
            $growthLabel = 'New';
        } elseif ($lastMonthSales > 0) {
            $salesGrowth = (($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100;
        }

        return [
            'sales' => round($sales, 2),
            'purchases' => round($purchases, 2),
            'gpm' => round($gpm, 2),
            'sales_growth' => round($salesGrowth, 2),
            'growth_label' => $growthLabel,
        ];
    }
}
