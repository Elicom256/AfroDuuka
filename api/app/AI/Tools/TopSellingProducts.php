<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class TopSellingProducts extends Tool
{
    public function name(): string
    {
        return 'top_selling_products';
    }

    public function description(): string
    {
        return 'Get the top selling products by quantity sold';
    }

    public function parameters(): array
    {
        return [
            'period' => [
                'type' => 'string',
                'description' => 'Period: last_7_days, last_30_days, this_month, last_month, or all',
                'default' => 'last_30_days',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of products to return (default 10)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $limit = min((int) ($parameters['limit'] ?? 10), 50);
        $period = $parameters['period'] ?? 'last_30_days';

        $query = SaleItem::select(
            'business_branch_product_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(subtotal) as total_revenue'),
            DB::raw('COUNT(DISTINCT sale_id) as order_count')
        )
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed'));

        if ($period !== 'all') {
            $query->where('created_at', '>=', $this->dateFromPeriod($period));
        }

        $products = $query->groupBy('business_branch_product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->with('businessBranchProduct')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->businessBranchProduct?->name ?? 'Unknown',
                'total_quantity_sold' => (int) $item->total_quantity,
                'total_revenue' => (float) $item->total_revenue,
                'order_count' => (int) $item->order_count,
                'current_stock' => $item->businessBranchProduct?->quantity ?? 0,
            ]);

        return [
            'products' => $products,
            'total' => $products->count(),
            'period' => $period,
        ];
    }

    protected function dateFromPeriod(string $period): string
    {
        $date = match ($period) {
            'last_7_days' => now()->subDays(7),
            'last_30_days' => now()->subDays(30),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(30),
        };
        return $date->toDateTimeString();
    }
}
