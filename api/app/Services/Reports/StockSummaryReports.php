<?php

namespace App\Services\Reports;

use App\Models\BusinessBranchProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockSummaryReports
{
    public function stockSummary(array $filters, User $user, int $perPage = 15): array
    {
        $baseQuery = BusinessBranchProduct::query()
            ->where('business_branch_id', $user->business_branch_id);

        $totalProducts = (int) $baseQuery->count();
        $totalStockQuantity = (float) $baseQuery->sum('quantity');
        $totalInventoryValue = (float) $baseQuery->select(DB::raw('SUM(quantity * cost_price) as inventory_value'))->value('inventory_value');
        $activeProducts = (int) $baseQuery->where('status', 'active')->count();
        $inactiveProducts = (int) $baseQuery->where('status', 'inactive')->count();

        $products = BusinessBranchProduct::query()
            ->where('business_branch_id', $user->business_branch_id)
            ->select(['id', 'name', 'quantity', 'cost_price', 'price', 'reorder_level', 'status'])
            ->paginate($perPage)
            ->toArray();

        return [
            'total_products' => $totalProducts,
            'total_stock_quantity' => $totalStockQuantity,
            'total_inventory_value' => round($totalInventoryValue, 2),
            'active_products' => $activeProducts,
            'inactive_products' => $inactiveProducts,
            'products' => $products,
        ];
    }
}
