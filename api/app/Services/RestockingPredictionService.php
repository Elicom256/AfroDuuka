<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RestockingPredictionService
{
    public function predictions(?array $branchIds, int $thresholdDays): array
    {
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

        $predictions = Product::with('productCategory')->get()
            ->map(function (Product $product) use ($salesVelocity, $periodDays, $thresholdDays) {
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

        return [
            'predictions' => $predictions,
            'at_risk_count' => $predictions->where('is_at_risk', true)->count(),
            'low_stock_count' => $predictions->where('is_low_stock', true)->count(),
            'not_selling_count' => $predictions->where(
                fn (array $prediction) => $prediction['total_sold_30_days'] === 0 && $prediction['quantity'] > 0
            )->count(),
            'threshold_days' => $thresholdDays,
        ];
    }
}