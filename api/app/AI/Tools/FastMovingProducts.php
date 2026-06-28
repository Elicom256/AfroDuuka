<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class FastMovingProducts extends Tool
{
    public function name(): string
    {
        return 'fast_moving_products';
    }

    public function description(): string
    {
        return 'Get the fastest moving products by sales volume';
    }

    public function parameters(): array
    {
        return [
            'days' => [
                'type' => 'integer',
                'description' => 'Number of days to consider (default 30)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of products to return (default 20)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $days = (int) ($parameters['days'] ?? 30);
        $limit = min((int) ($parameters['limit'] ?? 20), 100);

        $threshold = now()->subDays($days);

        $products = SaleItem::select(
            'business_branch_product_id',
            DB::raw('SUM(quantity) as total_sold'),
            DB::raw('COUNT(*) as sale_count')
        )
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed'))
            ->where('created_at', '>=', $threshold)
            ->groupBy('business_branch_product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->with('businessBranchProduct')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->businessBranchProduct?->name ?? 'Unknown',
                'total_sold' => (int) $item->total_sold,
                'sale_count' => (int) $item->sale_count,
                'current_stock' => $item->businessBranchProduct?->quantity ?? 0,
            ]);

        return [
            'products' => $products,
            'total' => $products->count(),
            'days_considered' => $days,
        ];
    }
}
