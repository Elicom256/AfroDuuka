<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\SaleItem;

class LatestSoldProduct extends Tool
{
    public function name(): string
    {
        return 'latest_sold_product';
    }

    public function description(): string
    {
        return 'Get the most recently sold products across all branches';
    }

    public function parameters(): array
    {
        return [
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of latest sold products to return (default 10)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $limit = min((int) ($parameters['limit'] ?? 10), 50);

        $items = SaleItem::with('sale.businessBranch', 'businessBranchProduct.product')
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed'))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'product' => $item->businessBranchProduct?->name ?? $item->businessBranchProduct?->product?->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'branch' => $item->sale?->businessBranch?->name,
                'sold_at' => $item->created_at->toDateTimeString(),
            ]);

        return [
            'latest_sold_products' => $items,
            'total' => $items->count(),
        ];
    }
}
