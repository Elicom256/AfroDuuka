<?php

namespace App\Services\Reports;

use App\Models\BusinessBranchProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeadStockReports
{
    public function deadStock(array $filters, User $user): array
    {
        $cutoffDate = Carbon::now()->subDays(90)->toDateString();

        $query = BusinessBranchProduct::query()
            ->where('business_branch_id', $user->business_branch_id)
            ->where(function ($query) use ($cutoffDate) {
                $query->whereNull('last_sold_at')
                    ->orWhere('last_sold_at', '<=', $cutoffDate);
            });

        $products = (clone $query)
            ->select(['id', 'name', 'quantity', 'cost_price', 'last_sold_at'])
            ->get();

        $deadStockValue = (float) (clone $query)
            ->select(DB::raw('SUM(quantity * cost_price) as dead_stock_value'))
            ->value('dead_stock_value');

        return [
            'dead_stock_count' => $products->count(),
            'dead_stock_value' => round($deadStockValue, 2),
            'products' => $products,
        ];
    }
}
