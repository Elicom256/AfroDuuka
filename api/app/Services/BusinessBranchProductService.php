<?php

namespace App\Services;

use App\Models\BusinessBranchProduct;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class BusinessBranchProductService
{
// **What product analytics should answer**
// ====== Analytics help answer: =======
//✅ Which products make the most money?
//✅ Which products sell fastest?
// ✅Which products stay too long in stock?
//✅ Which products are about to run out?
// ✅Which products are dead stock?
// Which products have poor margins?
// Which products are trending?
//✅ How much inventory value is sitting in the store?
    public function analytics(BusinessBranchProduct $products, string $business_branch_id)
    {
        $totalInventoryValue = $products->where("quantity", ">", 0)
                               ->selectRaw("SUM(quantity * cost_price) as totalIV")
                               ->value("totalIV");

        $totalPotentialRevenue = $products->where("quantity", ">", 0)
                               ->selectRaw("SUM(quantity * price) as totalProfit")
                               ->value("totalProfit");

       $totalExpectedProfit = $products->where("quantity", ">", 0)
                               ->selectRaw("SUM((price - cost_price) * quantity) as totalExpectedProfit")
                               ->value("totalExpectedProfit");

       $lowStock = $products->where("quantity", ">", 0)
                               ->whereColumn("quantity", "<=", "reorder_level")
                               ->count();  

      $outOfStock = $products->where("quantity", "<=", 0)
                            ->count();     
                            
    $statusBreakdown = $products->selectRaw('status, COUNT(*) as totalByStatus')
                      ->groupBy('status')
                      ->get();
//    slow moving stock  **give me products that DO NOT have sales within 30 days**
     $slowMoving = $products->where("quantity", "<=", 0)
                   ->where(function ($q){
                    $q->whereNull("last_sold_at")
                    ->orWhere("last_sold_at", "<=", now()->subDays(30));
                   })
                   ->get();
    //    dead stock  
       $deadStock = $products->where("quantity", "<=", 0)
                   ->where(function ($q){
                    $q->whereNull("last_sold_at")
                    ->orWhere("last_sold_at", "<=", now()->subDays(90));
                   })
                   ->get();            
                   
    $fastMoving = $products->where('last_sold_at', '>=', now()->subDays(7))
                  ->orderByDesc('last_sold_at')
                  ->get();

    //  ✅ Which products make the most money?   
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

    $poorMarginProducts = $products
                         ->where('quantity', '>', 0)
                         ->where('markup_percentage', '<=', 0.20)
                         ->orderBy('markup_percentage')
                         ->take(10)
                         ->get();             
        return [
            "totalInventoryValue" => $totalInventoryValue,
            "totalPotentialRevenue" => $totalPotentialRevenue,
            "totalExpectedProfit" => $totalExpectedProfit,
            "lowStock" => $lowStock,
            "outOfStock" => $outOfStock,
            "statusBreakdown" => $statusBreakdown,
            "slowMoving" => $slowMoving,
            "deadStock" => $deadStock,
            "fastMoving" => $fastMoving,
            "topProducts" => $topProducts,
            "poorMarginProducts" => $poorMarginProducts
        ];
    }
}