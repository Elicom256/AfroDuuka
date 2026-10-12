<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ProductService;
use App\Services\RestockingPredictionService;
use App\Support\Auth\RolePermissions;
use App\Support\ListLimit;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected ProductService $productService;

    protected InventoryService $inventoryService;

    protected RestockingPredictionService $restockingPredictionService;

    public function __construct(
        ProductService $productService,
        InventoryService $inventoryService,
        RestockingPredictionService $restockingPredictionService,
    ) {
        $this->productService = $productService;
        $this->inventoryService = $inventoryService;
        $this->restockingPredictionService = $restockingPredictionService;
    }

    public function index()
    {
        $products = ListLimit::apply(
            Product::with(['productCategory', 'taxCategory', 'attachments'])
                ->when(
                    request()->filled('business_branch_id'),
                    fn ($query) => $query->where('business_branch_id', request('business_branch_id'))
                )
                ->orderBy('id', 'asc'),
            request(),
            ListLimit::RECORDS
        );

        return response()->json([
            'message' => 'Products fetched',
            'products' => $products,
            ...ListLimit::truncationNotice(),
        ], 200);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();
        $product = Product::create($validated);

        return response()->json(['message' => 'Product Created Successfully!', 'product' => $product], 201);
    }

    public function show(string $product)
    {
        $product = Product::with(['productCategory', 'taxCategory', 'attachments'])->findOrFail($product);
        $this->authorize('view', $product);

        return response()->json(['message' => 'Product Fetched Successfully!', 'product' => $product], 200);
    }

    public function inventoryAnalytics()
    {
        try {
            $inventory = $this->productService->analytics();

            return response()->json([
                'message' => 'Fetch inventory analytics!',
                'data' => $inventory,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch inventory analytics!',
                'error' => 'An error occurred. Please try again.',
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
                'error' => 'An error occurred. Please try again.',
            ], 500);
        }
    }

    public function restocking()
    {
        try {
            $resolved = EffectiveBranchScope::branchesFor(Auth::user());
            $branchIds = $resolved !== null ? $resolved[1] : null;
            $thresholdDays = (int) request()->query('threshold', 14);
            $data = $this->restockingPredictionService->predictions($branchIds, $thresholdDays);

            return response()->json([
                'message' => 'Restocking predictions fetched',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch restocking predictions',
                'error' => 'An error occurred. Please try again.',
            ], 500);
        }
    }

    public function productMetrics(Product $product)
    {
        try {
            $period = request()->query('period', 'last_7_days');
            $allowedPeriods = ['last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'last_year'];
            if (! in_array($period, $allowedPeriods)) {
                $period = 'last_7_days';
            }
            $data = $this->productService->productPerformance($product, $period);

            return response()->json([
                'message' => 'Fetched Product Metrics!',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch Product Metrics!',
                'error' => 'An error occurred. Please try again.',
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

        return response()->json(['message' => 'Product Updated Successfully!', 'product' => $product], 201);
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
            'message' => 'Product stock adjusted successfully!',
            'product' => $product->refresh(),
        ], 201);
    }

    public function destroy(string $product)
    {
        $product = Product::findOrFail($product);
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['message' => 'Product Deleted Successfully!', 'product' => $product], 201);
    }
}
