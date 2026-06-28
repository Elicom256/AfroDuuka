<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\BusinessBranchProduct;

class LowStockProducts extends Tool
{
    public function name(): string
    {
        return 'low_stock_products';
    }

    public function description(): string
    {
        return 'Get products where stock quantity is at or below the reorder level';
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
        $query = BusinessBranchProduct::whereColumn('quantity', '<=', 'reorder_level')
            ->where('quantity', '>', 0);

        if (!empty($parameters['branch_id'])) {
            $query->where('business_branch_id', $parameters['branch_id']);
        }

        $products = $query->with('product.category')
            ->orderByRaw('(quantity * 1.0 / NULLIF(reorder_level, 0)) asc')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->product?->sku,
                'quantity' => $p->quantity,
                'reorder_level' => $p->reorder_level,
                'branch_id' => $p->business_branch_id,
                'status' => $p->status,
            ]);

        return [
            'products' => $products,
            'total' => $products->count(),
        ];
    }
}
