<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Product;

class OutOfStockProducts extends Tool
{
    public function name(): string
    {
        return 'out_of_stock_products';
    }

    public function description(): string
    {
        return 'Get products that are currently out of stock (quantity is zero)';
    }

    public function parameters(): array
    {
        return [
            'branch_id' => [
                'type' => 'integer',
                'description' => 'Filter by branch ID (optional)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $query = Product::where('quantity', '<=', 0);

        if (!empty($parameters['branch_id'])) {
            $query->where('business_branch_id', $parameters['branch_id']);
        }

        $products = $query->with('productCategory')
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'quantity' => $p->quantity,
                'branch_id' => $p->business_branch_id,
            ]);

        return [
            'products' => $products,
            'total' => $products->count(),
        ];
    }
}
