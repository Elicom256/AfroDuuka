<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\StockMovement;

class ProductHistory extends Tool
{
    public function name(): string
    {
        return 'product_history';
    }

    public function description(): string
    {
        return 'Get stock movement history for a specific product';
    }

    public function parameters(): array
    {
        return [
            'product_id' => [
                'type' => 'integer',
                'description' => 'The ID of the branch product to get history for',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of records to return (default 20)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $productId = $parameters['product_id'] ?? null;
        $limit = min((int) ($parameters['limit'] ?? 20), 100);

        if (!$productId) {
            if (!empty($parameters['query'])) {
                $productId = $parameters['query'];
            } else {
                return ['message' => 'Please provide a product ID or name.'];
            }
        }

        $movements = StockMovement::where('product_id', $productId)
            ->with('product')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'quantity' => $m->quantity,
                'reference' => $m->reference_type ? "{$m->reference_type}#{$m->reference_id}" : null,
                'notes' => $m->notes,
                'date' => $m->created_at->toDateTimeString(),
            ]);

        $product = \App\Models\Product::find($productId);

        return [
            'product_name' => $product?->name ?? 'Unknown',
            'current_quantity' => $product?->quantity ?? 0,
            'movements' => $movements,
            'total_movements' => $movements->count(),
        ];
    }
}
