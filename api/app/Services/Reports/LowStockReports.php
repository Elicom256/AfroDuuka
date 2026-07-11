<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\User;

class LowStockReports
{
    public function lowStock(array $filters, User $user): array
    {
        $products = Product::query()
            ->where('business_branch_id', $user->business_branch_id)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->select(['id', 'name', 'quantity', 'reorder_level', 'cost_price', 'price', 'status'])
            ->get();

        return [
            'low_stock_count' => $products->count(),
            'products' => $products,
        ];
    }
}
