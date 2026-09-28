<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ProductService;
use App\Support\Auth\RolePermissions;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected ProductService $productService;

    protected InventoryService $inventoryService;

    public function __construct(ProductService $productService, InventoryService $inventoryService)
    {
        $this->productService = $productService;
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        $products = Product::with(["productCategory", "taxCategory", "attachments"])
            ->orderBy("id", "asc")
            ->get();

        return response()->json(["message" => "Products fetched", "products" => $products], 200);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();
        $product = Product::create($validated);

        return response()->json(["message" => "Product Created Successfully!", "product" => $product], 201);
    }

    public function show(string $product)
    {
        $product = Product::with(["productCategory", "taxCategory", "attachments"])->findOrFail($product);
        $this->authorize('view', $product);

        return response()->json(["message" => "Product Fetched Successfully!", "product" => $product], 200);
    }

    public function inventoryAnalytics()
    {
        try {
            $inventory = $this->productService->analytics();
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
            $now = now()->startOfDay();
            $dangerDate = (clone $now)->addDays(7);
            $expiryWindow = (clone $now)->addDays(30);

            $query = Product::whereNotNull('expiry_date');

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
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            $branchIds = $resolved !== null ? $resolved[1] : null;
            $thresholdDays = (int) request()->query('threshold', 14);
            $periodDays = 30;

            $lookbackDate = now()->subDays($periodDays);

            $salesVelocity = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereNull('sales.deleted_at')
                ->when($branchIds !== null, function ($query) use ($branchIds) {
                    $query->whereIn('sales.business_branch_id', $branchIds);
                })
                ->where('sales.created_at', '>=', $lookbackDate)
                ->select(
                    'sale_items.product_id',
                    DB::raw('SUM(sale_items.quantity) as total_sold'),
                    DB::raw('COUNT(DISTINCT sales.id) as sale_count')
                )
                ->groupBy('sale_items.product_id')
                ->get()
                ->keyBy('product_id');

            $products = Product::with('productCategory')->get();

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

    public function update(UpdateProductRequest $request, string $product)
    {
        $product = Product::findOrFail($product);
        $this->authorize('update', $product);

        $validated = $request->validated();

        if (RolePermissions::isRestricted($request->user())) {
            return $this->adjustStock($product, $validated);
        }

        // Pass change_reason to the model so PriceHistoryObserver can pick it up
        if (isset($validated['change_reason'])) {
            $product->priceChangeReason = $validated['change_reason'];
        }

        $product->update($validated);
        return response()->json(["message" => "Product Updated Successfully!", "product" => $product], 201);
    }

    /**
     * Apply a counted stock level for a role that may not edit the catalogue.
     *
     * The request carries the resulting quantity because that is what a stock take
     * observes. The difference against the recorded level is what actually moved, so
     * that is what gets booked — through InventoryService, not a bare update, so a
     * stock_movements row explains the new number. Writing `quantity` straight onto the
     * product would leave the ledger disagreeing with the shelf.
     */
    private function adjustStock(Product $product, array $validated): JsonResponse
    {
        $delta = (int) $validated['quantity'] - (int) $product->quantity;

        if ($delta !== 0) {
            $this->inventoryService->adjust(
                $product,
                $delta,
                $validated['adjustment_notes'] ?? null,
                $validated['adjustment_reason'] ?? 'stock_take',
            );
        }

        return response()->json([
            "message" => "Product stock adjusted successfully!",
            "product" => $product->refresh(),
        ], 201);
    }

    public function destroy(string $product)
    {
        $product = Product::findOrFail($product);
        $this->authorize('delete', $product);

        $product->delete();
        return response()->json(["message" => "Product Deleted Successfully!", "product" => $product], 201);
    }
}
