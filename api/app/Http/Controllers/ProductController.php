<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $branchId =  Auth::user()->business_branch_id;
        $products = Product::where("business_branch_id", $branchId)
                     ->with("productCategory" )
                     ->orderBy("id", "asc")
                     ->get();
        return response()->json(["message" => "Products fetched", "products" => $products], 200);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $product = Product::create($validated);
        return response()->json(["message" => "Product Created Successfully!", "product" => $product], 201);
    }

    public function show(string $product)
    {
        $product = Product::with("productCategory")->findOrFail($product);
        return response()->json(["message" => "Product Fetched Successfully!", "product" => $product], 200);
    }

    public function inventoryAnalytics()
    {
        try {
            $business_branch_id = Auth::user()->business_branch_id;
            $inventory = $this->productService->analytics($business_branch_id);
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

    public function expiringAnalytics()
    {
        try {
            $branchId = Auth::user()->business_branch_id;
            $now = now()->startOfDay();
            $dangerDate = (clone $now)->addDays(7);
            $expiryWindow = (clone $now)->addDays(30);

            $query = Product::whereNotNull('expiry_date')
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

    public function restocking()
    {
        try {
            $branchId = Auth::user()->business_branch_id;
            $thresholdDays = (int) request()->query('threshold', 14);
            $periodDays = 30;

            $lookbackDate = now()->subDays($periodDays);

            $salesVelocity = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.business_branch_id', $branchId)
                ->where('sales.created_at', '>=', $lookbackDate)
                ->whereNull('sales.deleted_at')
                ->select(
                    'sale_items.product_id',
                    DB::raw('SUM(sale_items.quantity) as total_sold'),
                    DB::raw('COUNT(DISTINCT sales.id) as sale_count')
                )
                ->groupBy('sale_items.product_id')
                ->get()
                ->keyBy('product_id');

            $products = Product::where('business_branch_id', $branchId)
                ->with('productCategory')
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
                    'sku' => $product->sku,
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

    public function productMetrics(Product $product)
    {
        try {
            $period = request()->query("period", "last_7_days");
            $allowedPeriods = ['last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'last_year'];
            if (!in_array($period, $allowedPeriods)) {
                $period = 'last_7_days';
            }
            $data = $this->productService->productPerformance($product, $period);
            return response()->json([
                "message" => "Fetched Product Metrics!",
                "data" => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Failed to fetch Product Metrics!",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();
        $product->update($validated);
        return response()->json(["message" => "Product Updated Successfully!", "product" => $product], 201);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(["message" => "Product Deleted Successfully!", "product" => $product], 201);
    }
}
