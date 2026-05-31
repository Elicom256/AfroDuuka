<?php

namespace App\Services\Reports;

use App\Models\BusinessBranchProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryValuationReports
{
    public function inventoryValuation(array $filters, User $user): array
    {
        $baseQuery = BusinessBranchProduct::query()
            ->where('business_branch_id', $user->business_branch_id);

        $totalInventoryValue = (float) (clone $baseQuery)
            ->select(DB::raw('SUM(quantity * cost_price) as total_inventory_value'))
            ->value('total_inventory_value');

        $totalQuantity = (int) (clone $baseQuery)
            ->select(DB::raw('SUM(quantity) as total_quantity'))
            ->value('total_quantity');

        $averageCostPrice = $totalQuantity > 0
            ? round($totalInventoryValue / $totalQuantity, 2)
            : 0;

        $statusGroups = (clone $baseQuery)
            ->select([
                'status',
                DB::raw('SUM(quantity * cost_price) as total_inventory_value'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('AVG(cost_price) as average_cost_price'),
            ])
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                return [
                    'status' => $row->status,
                    'total_inventory_value' => (float) $row->total_inventory_value,
                    'total_quantity' => (int) $row->total_quantity,
                    'average_cost_price' => round((float) $row->average_cost_price, 2),
                ];
            })
            ->toArray();

        $categoryGroups = (clone $baseQuery)
            ->leftJoin('products', 'business_branch_products.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select([
                DB::raw("COALESCE(categories.name, 'Uncategorized') as category_name"),
                DB::raw('SUM(business_branch_products.quantity * business_branch_products.cost_price) as total_inventory_value'),
                DB::raw('SUM(business_branch_products.quantity) as total_quantity'),
            ])
            ->groupBy('category_name')
            ->get()
            ->map(function ($row) {
                return [
                    'category_name' => $row->category_name,
                    'total_inventory_value' => (float) $row->total_inventory_value,
                    'total_quantity' => (int) $row->total_quantity,
                ];
            })
            ->toArray();

        return [
            'total_inventory_value' => round($totalInventoryValue, 2),
            'total_quantity' => $totalQuantity,
            'average_cost_price' => $averageCostPrice,
            'grouped_by_status' => $statusGroups,
            'grouped_by_category' => $categoryGroups,
        ];
    }
}
