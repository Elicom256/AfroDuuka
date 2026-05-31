<?php

namespace App\Services\Reports;

use App\Models\BusinessBranchProduct;
use App\Models\User;

class OutOfStockReports
{
    public function outOfStock(array $filters, User $user): array
    {
        $products = BusinessBranchProduct::query()
            ->where('business_branch_id', $user->business_branch_id)
            ->where('quantity', '<=', 0)
            ->select(['id', 'name', 'last_sold_at', 'status'])
            ->get();

        return [
            'out_of_stock_count' => $products->count(),
            'products' => $products,
        ];
    }
}
