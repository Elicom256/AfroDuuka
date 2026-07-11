<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Product;

class WorstSellingProducts extends Tool
{
    public function name(): string
    {
        return 'worst_selling_products';
    }

    public function description(): string
    {
        return 'Get the worst selling products (never sold or very low sales)';
    }

    public function parameters(): array
    {
        return [
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of products to return (default 10)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $limit = min((int) ($parameters['limit'] ?? 10), 50);

        $products = Product::whereNull('last_sold_at')
            ->with('productCategory')
            ->orderBy('quantity', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'quantity' => $p->quantity,
                'price' => $p->price,
                'cost_price' => $p->cost_price,
                'last_sold_at' => 'Never sold',
            ]);

        return [
            'products' => $products,
            'total' => $products->count(),
        ];
    }
}
