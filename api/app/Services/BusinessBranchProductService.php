<?php

namespace App\Services;

use App\Models\BusinessBranchProduct;
use App\Models\SaleItem;
// use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Facades\DB;

class BusinessBranchProductService
{
public function analytics(string $business_branch_id)
{
    $products = BusinessBranchProduct::query()
        ->where("business_branch_id", $business_branch_id);

    $totalInventoryValue = (clone $products)
        // ->where("quantity", ">", 0)
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
        ->where("quantity", ">", 0)
        ->whereColumn("quantity", "<=", "reorder_level")
        ->count();

    $outOfStock = (clone $products)
        ->where("quantity", "<=", 0)
        ->count();

    $statusBreakdown = (clone $products)
        ->selectRaw('status, COUNT(*) as totalByStatus')
        ->groupBy('status')
        ->get();

    // products without sales in 30 days
    $slowMoving = (clone $products)
        ->where("quantity", ">", 0)
        ->where(function ($q) {
            $q->whereNull("last_sold_at")
              ->orWhere("last_sold_at", "<=", now()->subDays(30));
        })
        ->get();

    // products without sales in 90 days
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

    $topProducts = BusinessBranchProduct::query()
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
}