<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Increase stock (PURCHASES)
     */
    public function stockIn(Product $product, int $quantity, ?string $referenceType = null, ?int $referenceId = null)
    {
        DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId) {

            $product->increment('quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    /**
     * Decrease stock (SALES)
     */
    public function stockOut(Product $product, int $quantity, ?string $referenceType = null, ?int $referenceId = null)
    {
        DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId) {

            // prevent negative stock
            if ($product->quantity < $quantity) {
                throw new \Exception("Insufficient stock for product: {$product->name}");
            }

            $product->decrement('quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    /**
     * Manual adjustment (admin fix)
     */
        public function adjust(Product $product, int $quantity, ?string $notes = null)
        {
            DB::transaction(function () use ($product, $quantity, $notes) {

            $product->increment('quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'notes' => $notes,
            ]);
        });
    }
}