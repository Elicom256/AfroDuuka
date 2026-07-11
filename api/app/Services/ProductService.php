<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    public function analytics(string $business_branch_id)
    {
        $products = Product::query()
            ->where("business_branch_id", $business_branch_id);

        $totalInventoryValue = (clone $products)
            ->selectRaw("SUM(quantity * cost_price) as total")
            ->first()
            ->total;
        $totalPotentialRevenue = (clone $products)
            ->where("quantity", ">", 0)
            ->selectRaw("COALESCE(SUM(quantity * price), 0) as total")
            ->first()
            ->total;

        $profits = (clone $products)
            ->where("quantity", ">", 0)
            ->selectRaw("COALESCE(SUM((price - cost_price) * quantity), 0) as total")
            ->first()
            ->total;

        $lowStock = (clone $products)
            ->where("quantity", ">=", 0)
            ->whereColumn("quantity", "<=", "reorder_level")
            ->count();

        $outOfStock = (clone $products)
            ->where("quantity", "<=", 0)
            ->count();

        $statusBreakdown = (clone $products)
            ->selectRaw('status, COUNT(*) as totalByStatus')
            ->groupBy('status')
            ->get();

        $slowMoving = (clone $products)
            ->where("quantity", ">", 0)
            ->where(function ($q) {
                $q->whereNull("last_sold_at")
                  ->orWhere("last_sold_at", "<=", now()->subDays(30));
            })
            ->get();

        $deadStock = (clone $products)
            ->where("quantity", ">", 0)
            ->where(function ($q) {
                $q->whereNull("last_sold_at")
                  ->orWhere("last_sold_at", "<=", now()->subDays(90));
            })
            ->get();

        $fastMoving = (clone $products)
            ->where('last_sold_at', '>=', now()->subDays(7))
            ->orderByDesc('last_sold_at')
            ->get();

        $topProducts = Product::query()
            ->where('business_branch_id', $business_branch_id)
            ->whereHas('saleItems')
            ->withSum('saleItems as total_revenue', 'subtotal')
            ->withSum('saleItems as total_quantity_sold', 'quantity')
            ->withSum([
                'saleItems as total_profit' => function ($q) {
                    $q->select(DB::raw(
                        'SUM(quantity * (price - cost_price))'
                    ));
                }
            ], DB::raw('quantity'))
            ->orderByDesc('total_profit')
            ->take(10)
            ->get();

        $poorMarginProducts = (clone $products)
            ->where('quantity', '>', 0)
            ->where('markup_percentage', '<=', 20)
            ->orderBy('markup_percentage')
            ->take(10)
            ->get();

        return [
            "totalInventoryValue" => $totalInventoryValue,
            "totalPotentialRevenue" => $totalPotentialRevenue,
            "totalExpectedProfit" => $profits,
            "lowStock" => $lowStock,
            "outOfStock" => $outOfStock,
            "statusBreakdown" => $statusBreakdown,
            "slowMoving" => $slowMoving,
            "deadStock" => $deadStock,
            "fastMoving" => $fastMoving,
            "topProducts" => $topProducts,
            "poorMarginProducts" => $poorMarginProducts,
        ];
    }

    public function productPerformance(Product $product, string $period = "last_7_days")
    {
        $dates = $this->analyticsTrendHelper->getPeriodDates($period);
        $date_range = [$dates["start"], $dates["end"]];

        $sales = SaleItem::where("product_id", $product->id)
            ->whereBetween("created_at", $date_range)
            ->sum("subtotal");

        $purchases = PurchaseItem::where("product_id", $product->id)
            ->whereBetween("created_at", $date_range)
            ->sum("subtotal");

        $gpm = 0;
        if ($sales > 0) {
            $gpm = (($sales - $purchases) / $sales) * 100;
        }

        $currentMonthSales = SaleItem::where("product_id", $product->id)
            ->whereBetween("created_at", [now()->startOfMonth(), now()->endOfMonth()])
            ->sum("subtotal");

        $lastMonthSales = SaleItem::where("product_id", $product->id)
            ->whereBetween("created_at", [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->sum("subtotal");

        $salesGrowth = 0;
        $growthLabel = null;
        if ($lastMonthSales == 0 && $currentMonthSales > 0) {
            $growthLabel = "New";
        } elseif ($lastMonthSales > 0) {
            $salesGrowth = (($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100;
        }

        return [
            "sales" => round($sales, 2),
            "purchases" => round($purchases, 2),
            "gpm" => round($gpm, 2),
            "sales_growth" => round($salesGrowth, 2),
            "growth_label" => $growthLabel
        ];
    }
}
