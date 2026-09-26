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
    public function stockIn(Product $product, int $quantity, ?string $referenceType = null, ?int $referenceId = null, ?string $movementKey = null)
    {
        DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $movementKey) {
            $lockedProduct = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $resolvedMovementKey = $movementKey ?? md5($product->id . ':' . $referenceType . ':' . $referenceId . ':' . $quantity . ':' . now()->timestamp);

            $existing = StockMovement::where('movement_key', $resolvedMovementKey)->first();
            if ($existing) {
                return $existing;
            }

            $lockedProduct->increment('quantity', $quantity);

            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_key' => $resolvedMovementKey,
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
    public function stockOut(Product $product, int $quantity, ?string $referenceType = null, ?int $referenceId = null, ?string $movementKey = null)
    {
        DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $movementKey) {
            $lockedProduct = Product::whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $resolvedMovementKey = $movementKey ?? md5($product->id . ':' . $referenceType . ':' . $referenceId . ':' . $quantity . ':' . now()->timestamp);

            $existing = StockMovement::where('movement_key', $resolvedMovementKey)->first();
            if ($existing) {
                return $existing;
            }

            // prevent negative stock
            if ($lockedProduct->quantity < $quantity) {
                throw new \Exception("Insufficient stock for product: {$lockedProduct->name}");
            }

            $lockedProduct->decrement('quantity', $quantity);

            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_key' => $resolvedMovementKey,
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
        public function adjust(Product $product, int $quantity, ?string $notes = null, ?string $movementKey = null)
    {
        DB::transaction(function () use ($product, $quantity, $notes, $movementKey) {
            $lockedProduct = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $resolvedMovementKey = $movementKey ?? md5($product->id . ':adjustment:' . $notes . ':' . $quantity . ':' . now()->timestamp);

            $existing = StockMovement::where('movement_key', $resolvedMovementKey)->first();
            if ($existing) {
                return $existing;
            }

            $lockedProduct->increment('quantity', $quantity);

            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_key' => $resolvedMovementKey,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'notes' => $notes,
            ]);
        });
    }
}