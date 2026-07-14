<?php

/*-----------------------------------------------------------------------------------
 * Controller: PriceHistoryController
 * -------------------------------
 * Exposes endpoints for querying price change history and analytics.
 * Follows the same convention as other controllers in the project:
 *   - Service injection in constructor
 *   - response()->json() with consistent message + data shape
 *   - Try-catch for error handling
 *---------------------------------------------------------------------------------*/

namespace App\Http\Controllers;

use App\Services\PriceHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceHistoryController extends Controller
{
    protected PriceHistoryService $priceHistoryService;

    public function __construct(PriceHistoryService $priceHistoryService)
    {
        $this->priceHistoryService = $priceHistoryService;
    }

    /**
     * Get paginated price timeline for a specific product.
     * GET /products/{product}/price-history
     */
    public function productTimeline(int $productId): JsonResponse
    {
        try {
            $perPage = (int) request()->query('per_page', 15);
            $history = $this->priceHistoryService->getProductTimeline($productId, $perPage);

            return response()->json([
                'message' => 'Product price timeline fetched',
                'data'    => $history,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch price timeline',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List latest changes across all products, filterable.
     * GET /price-history
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['product_id', 'date_from', 'date_to']);
            $perPage = (int) $request->query('per_page', 15);
            $history = $this->priceHistoryService->getLatestChanges($filters, $perPage);

            return response()->json([
                'message' => 'Price history fetched',
                'data'    => $history,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch price history',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Price change analytics over time.
     * GET /price-history/analytics?period=last_7_days
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'last_7_days');
            $allowedPeriods = ['last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'last_year'];

            if (!in_array($period, $allowedPeriods)) {
                $period = 'last_7_days';
            }

            $data = $this->priceHistoryService->priceChangeAnalytics($period);

            return response()->json([
                'message' => 'Price change analytics fetched',
                'data'    => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch price change analytics',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
