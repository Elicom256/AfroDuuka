<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\BusinessBranchProduct;

class DailyBusinessSummary extends Tool
{
    public function name(): string
    {
        return 'daily_business_summary';
    }

    public function description(): string
    {
        return 'Get a summary of today\'s business activities including sales, purchases, and stock alerts';
    }

    public function parameters(): array
    {
        return [
            'date' => [
                'type' => 'string',
                'description' => 'Date in YYYY-MM-DD format (defaults to today)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $date = !empty($parameters['date']) ? $parameters['date'] : today()->toDateString();

        $sales = Sale::whereDate('created_at', $date)->get();
        $purchases = Purchase::whereDate('created_at', $date)->get();
        $lowStockAlerts = BusinessBranchProduct::whereColumn('quantity', '<=', 'reorder_level')
            ->where('quantity', '>', 0)
            ->count();
        $outOfStock = BusinessBranchProduct::where('quantity', '<=', 0)->count();

        return [
            'date' => $date,
            'sales' => [
                'count' => $sales->count(),
                'total' => (float) $sales->where('status', 'completed')->sum('total_amount'),
                'pending' => $sales->where('status', 'pending')->count(),
            ],
            'purchases' => [
                'count' => $purchases->count(),
                'total' => (float) $purchases->where('status', 'completed')->sum('total_amount'),
            ],
            'alerts' => [
                'low_stock' => $lowStockAlerts,
                'out_of_stock' => $outOfStock,
            ],
        ];
    }
}
