<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessBranchProductRequest;
use App\Http\Requests\UpdateBusinessBranchProductRequest;
use App\Models\BusinessBranchProduct;
use App\Services\BusinessBranchProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BusinessBranchProductController extends Controller
{
    protected BusinessBranchProductService $businessBranchProductService;
    public function __construct(BusinessBranchProductService $businessBranchProductService)
    {
        $this->businessBranchProductService = $businessBranchProductService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = Auth::user()->business_branch_id;
        $products = BusinessBranchProduct::where("business_branch_id", $branchId)->with("product")->get();
        return response()->json(["message" => "Products fetched", "products" => $products], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessBranchProductRequest $request)
    {
        $validated = $request->validated();
        $product = BusinessBranchProduct::create($validated);
        return response()->json(["message" => "Product Created Successfully!", "product" => $product], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $businessBranchProduct)
    {
        $branchId = Auth::user()->business_branch_id;
        $product = BusinessBranchProduct::where("id", $businessBranchProduct)
                   ->where("business_branch_id", $branchId)
                   ->with("product")->first();
        // dd($businessBranchProduct);

        return response()->json(["message" => "Product Fetched Successfully!", "product" => $product], 200);
    }

    public function inventoryAnalytics(){
        
        try {
            $business_branch_id = Auth::user()->business_branch_id;
            $inventory = $this->businessBranchProductService->analytics($business_branch_id);
            return response()->json([
            "message" => "Fetch inventory analytics!",
            "data" => $inventory
           ], 200);
        } catch (\Exception $e) {
           return response()->json([
            "message" => "Failed to fetch inventory analytics!",
            "error" => $e->getMessage()
           ], 500);
        }
    }

    // expiring products analytics
    public function expiringAnalytics()
    {
        try {
            $branchId = Auth::user()->business_branch_id;
            $now = now()->startOfDay();
            $dangerDate = (clone $now)->addDays(7);
            $expiryWindow = (clone $now)->addDays(30);

            $query = BusinessBranchProduct::whereNotNull('expiry_date')
                ->where('business_branch_id', $branchId);

            $expiredCount = (clone $query)->where('expiry_date', '<', $now)->count();
            $expiringCount = (clone $query)->whereBetween('expiry_date', [$now, $expiryWindow])->count();
            $dangerCount = (clone $query)->whereBetween('expiry_date', [$now, $dangerDate])->count();

            return response()->json([
                'message' => 'Expiring products analytics fetched',
                'data' => [
                    'expired_count' => $expiredCount,
                    'expiring_count' => $expiringCount,
                    'danger_count' => $dangerCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch expiring analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // smart restocking predictions
    public function restocking()
    {
        try {
            $branchId = Auth::user()->business_branch_id;
            $thresholdDays = (int) request()->query('threshold', 14);
            $periodDays = 30;

            $lookbackDate = now()->subDays($periodDays);

            // Get sales velocity per product over the lookback period
            $salesVelocity = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.business_branch_id', $branchId)
                ->where('sales.created_at', '>=', $lookbackDate)
                ->whereNull('sales.deleted_at')
                ->select(
                    'sale_items.business_branch_product_id',
                    DB::raw('SUM(sale_items.quantity) as total_sold'),
                    DB::raw('COUNT(DISTINCT sales.id) as sale_count')
                )
                ->groupBy('sale_items.business_branch_product_id')
                ->get()
                ->keyBy('business_branch_product_id');

            $products = BusinessBranchProduct::where('business_branch_id', $branchId)
                ->with('product')
                ->get();

            $predictions = $products->map(function ($product) use ($salesVelocity, $periodDays, $thresholdDays) {
                $velocityData = $salesVelocity->get($product->id);
                $totalSold = $velocityData ? (int) $velocityData->total_sold : 0;
                $dailyVelocity = $totalSold / max($periodDays, 1);
                $daysUntilOut = $dailyVelocity > 0
                    ? (int) floor($product->quantity / $dailyVelocity)
                    : null;
                $isAtRisk = $daysUntilOut !== null && $daysUntilOut <= $thresholdDays;
                $isLowStock = $product->quantity <= $product->reorder_level && $product->reorder_level > 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->product?->sku,
                    'quantity' => $product->quantity,
                    'reorder_level' => $product->reorder_level,
                    'daily_sales_velocity' => round($dailyVelocity, 2),
                    'total_sold_30_days' => $totalSold,
                    'days_until_out' => $daysUntilOut,
                    'is_at_risk' => $isAtRisk,
                    'is_low_stock' => $isLowStock,
                    'last_sold_at' => $product->last_sold_at?->format('Y-m-d'),
                ];
            });

            $atRisk = $predictions->where('is_at_risk', true)->sortBy('days_until_out')->values();
            $lowStock = $predictions->where('is_low_stock', true)->sortBy('quantity')->values();
            $notSelling = $predictions->where('total_sold_30_days', 0)->where('quantity', '>', 0)->values();

            return response()->json([
                'message' => 'Restocking predictions fetched',
                'data' => [
                    'predictions' => $predictions,
                    'at_risk_count' => $atRisk->count(),
                    'low_stock_count' => $lowStock->count(),
                    'not_selling_count' => $notSelling->count(),
                    'threshold_days' => $thresholdDays,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch restocking predictions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // product performance analytics
    public function productMetrics(BusinessBranchProduct $businessBranchProduct){
        
        try {
            $period = request()->query("period", "last_7_days");
            $allowedPeriods = ['last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'last_year'];
            if (!in_array($period, $allowedPeriods)) {
                $period = 'last_7_days'; // fallback
            }
            $product = $this->businessBranchProductService->productPerformance($businessBranchProduct, $period);
            return response()->json([
            "message" => "Fetched Product Metrics!",
            "data" => $product
           ], 200);
        } catch (\Exception $e) {
             return response()->json([
            "message" => "Failed to fetch Product Metrics!",
            "error" => $e->getMessage()
           ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessBranchProductRequest $request, BusinessBranchProduct $businessBranchProduct)
    {
        $validated = $request->validated();
        $businessBranchProduct->update($validated);
        return response()->json(["message" => "Product Updated Successfully!", "product" => $businessBranchProduct], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessBranchProduct $businessBranchProduct)
    {
        $businessBranchProduct->delete();
        return response()->json(["message" => "Product Deleted Successfully!", "product" => $businessBranchProduct], 201);

    }
}
