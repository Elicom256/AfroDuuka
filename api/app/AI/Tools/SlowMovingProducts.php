<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\BusinessBranchProduct;

class SlowMovingProducts extends Tool
{
    public function name(): string
    {
        return 'slow_moving_products';
    }

    public function description(): string
    {
        return 'Get products with low sales velocity (not sold recently or low turnover)';
    }

    public function parameters(): array
    {
        return [
            'days' => [
                'type' => 'integer',
                'description' => 'Number of days to consider (default 90)',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of products to return (default 20)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $days = (int) ($parameters['days'] ?? 90);
        $limit = min((int) ($parameters['limit'] ?? 20), 100);

        $threshold = now()->subDays($days);

        $products = BusinessBranchProduct::where(function ($q) use ($threshold) {
            $q->whereNull('last_sold_at')
              ->orWhere('last_sold_at', '<', $threshold);
        })
            ->where('quantity', '>', 0)
            ->with('product.category')
            ->orderBy('last_sold_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->product?->sku,
                'quantity' => $p->quantity,
                'price' => $p->price,
                'last_sold_at' => $p->last_sold_at?->toDateString() ?? 'Never sold',
                'days_since_last_sale' => $p->last_sold_at ? $p->last_sold_at->diffInDays() : null,
            ]);

        return [
            'products' => $products,
            'total' => $products->count(),
            'days_considered' => $days,
        ];
    }
}
