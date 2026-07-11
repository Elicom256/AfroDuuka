<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Product;

class ProductProfit extends Tool
{
    public function name(): string
    {
        return 'product_profit';
    }

    public function description(): string
    {
        return 'Get profit analysis per product (selling price vs cost price)';
    }

    public function parameters(): array
    {
        return [
            'sort_by' => [
                'type' => 'string',
                'description' => 'Sort by: profit_margin, profit_amount, or name (default profit_margin)',
                'default' => 'profit_margin',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of products to return (default 20)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $sortBy = $parameters['sort_by'] ?? 'profit_margin';
        $limit = min((int) ($parameters['limit'] ?? 20), 100);

        $products = Product::where('quantity', '>', 0)
            ->where('price', '>', 0)
            ->where('cost_price', '>', 0)
            ->with('productCategory')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'selling_price' => $p->price,
                'cost_price' => $p->cost_price,
                'profit_per_unit' => round($p->price - $p->cost_price, 2),
                'profit_margin_percent' => $p->price > 0 ? round((($p->price - $p->cost_price) / $p->price) * 100, 2) : 0,
                'quantity' => $p->quantity,
                'total_potential_profit' => round(($p->price - $p->cost_price) * $p->quantity, 2),
            ])
            ->sortByDesc(fn ($item) => match ($sortBy) {
                'profit_amount' => $item['profit_per_unit'],
                'name' => $item['name'],
                default => $item['profit_margin_percent'],
            })
            ->values();

        $avgMargin = $products->avg('profit_margin_percent');

        return [
            'products' => $products,
            'total' => $products->count(),
            'average_margin_percent' => round($avgMargin, 2),
        ];
    }
}
