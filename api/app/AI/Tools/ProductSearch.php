<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Product;

class ProductSearch extends Tool
{
    public function name(): string
    {
        return 'product_search';
    }

    public function description(): string
    {
        return 'Search for products by name, SKU, or barcode';
    }

    public function parameters(): array
    {
        return [
            'query' => [
                'type' => 'string',
                'description' => 'Search term for product name, SKU, or barcode',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $query = $parameters['query'] ?? '';

        if (empty($query)) {
            return ['message' => 'Please provide a search term.'];
        }

        $products = Product::where(function ($q) use ($query) {
            $q->where('name', 'ilike', "%{$query}%")
              ->orWhere('sku', 'ilike', "%{$query}%")
              ->orWhere('barcode', 'ilike', "%{$query}%");
        })
            ->with('category')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'category' => $p->category?->name,
                'status' => $p->status,
            ]);

        return [
            'products' => $products,
            'total' => $products->count(),
        ];
    }
}
