<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

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

        $slowMoving = (clone $products)
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('last_sold_at')
                    ->orWhere('last_sold_at', '<=', now()->subDays(30));
            })
            ->get();

        $deadStock = (clone $products)
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('last_sold_at')
                    ->orWhere('last_sold_at', '<=', now()->subDays(90));
            })
            ->get();

        $fastMoving = (clone $products)
            ->where('last_sold_at', '>=', now()->subDays(7))
            ->orderByDesc('last_sold_at')
            ->get();

        // The expression has to be repeated in the WHERE and ORDER BY rather than
        // referenced by its alias. PostgreSQL accepts a select-list alias in ORDER BY but
        // not in HAVING -- HAVING is evaluated before the list is projected, and with no
        // GROUP BY it is a grouping error outright. This is a row filter, so it belongs in
        // WHERE. It is also the statement that takes the longest to run in the whole set.
        $poorMarginProducts = (clone $products)
            ->where('quantity', '>', 0)
            ->where('cost_price', '>', 0)
            ->where('selling_price', '>', 0)
            ->whereRaw('((selling_price - cost_price) / cost_price) * 100 <= 20')
            ->select('products.*')
            ->orderByRaw('((selling_price - cost_price) / cost_price) * 100')
            ->take(10)
            ->get();

        return [
            'total_products' => $totalProducts,
            'totalInventoryValue' => $totalInventoryValue,
            'totalPotentialRevenue' => $totalPotentialRevenue,
            'totalExpectedProfit' => $profits,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'statusBreakdown' => $statusBreakdown,
            'slowMoving' => $slowMoving,
            'deadStock' => $deadStock,
            'fastMoving' => $fastMoving,
            'poorMarginProducts' => $poorMarginProducts,
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
