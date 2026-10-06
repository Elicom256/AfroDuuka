<?php

namespace App\Services;

use App\Enums\CashFlowType;
use App\Models\CashFlow;
use App\Models\Product;
use App\Models\ProductLoss;
use App\Models\StockMovement;
use App\Support\Money;
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
            $resolvedMovementKey = $movementKey ?? md5($product->id.':'.$referenceType.':'.$referenceId.':'.$quantity.':'.now()->timestamp);

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

            $resolvedMovementKey = $movementKey ?? md5($product->id.':'.$referenceType.':'.$referenceId.':'.$quantity.':'.now()->timestamp);

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
     * Manual adjustment / loss flow.
     * Positive quantity increases stock, negative quantity reduces it.
     * Reason codes: damaged, expired, lost, stock_take, other.
     */
    public function adjust(Product $product, int $quantity, ?string $notes = null, ?string $reason = null, ?string $movementKey = null)
    {
        DB::transaction(function () use ($product, $quantity, $notes, $reason, $movementKey) {
            $lockedProduct = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $normalizedReason = $reason ?? 'adjustment';
            $allowedReasons = ['adjustment', 'damaged', 'expired', 'lost', 'stock_take', 'other'];

            if (! in_array($normalizedReason, $allowedReasons, true)) {
                throw new \InvalidArgumentException("Unsupported stock adjustment reason: {$normalizedReason}");
            }

            $resolvedMovementKey = $movementKey ?? md5($product->id.':adjustment:'.$normalizedReason.':'.$quantity.':'.now()->timestamp);

            $existing = StockMovement::where('movement_key', $resolvedMovementKey)->first();
            if ($existing) {
                return $existing;
            }

            if ($quantity < 0 && abs($quantity) > $lockedProduct->quantity) {
                throw new \Exception("Cannot reduce stock below zero for product: {$lockedProduct->name}");
            }

            $lockedProduct->increment('quantity', $quantity);

            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_key' => $resolvedMovementKey,
                'type' => 'adjustment',
                'quantity' => abs($quantity),
                'reason' => $normalizedReason,
                'notes' => $notes,
            ]);
        });
    }

    public function writeOff(Product $product, int $quantity, string $reason, ?string $notes = null, ?string $movementKey = null, ?string $lossDate = null): ProductLoss
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Write-off quantity must be greater than zero.');
        }

        $allowedReasons = ['damaged', 'expired', 'lost'];
        if (! in_array($reason, $allowedReasons, true)) {
            throw new \InvalidArgumentException('Write-off reason must be one of: '.implode(', ', $allowedReasons));
        }

        $resolvedMovementKey = $movementKey ?? md5($product->id.':writeoff:'.$reason.':'.$quantity.':'.($lossDate ?? now()->toDateString()));

        return DB::transaction(function () use ($product, $quantity, $reason, $notes, $resolvedMovementKey, $lossDate) {
            $lockedProduct = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            $existingMovement = StockMovement::where('movement_key', $resolvedMovementKey)->first();
            if ($existingMovement) {
                return ProductLoss::where('stock_movement_id', $existingMovement->id)->firstOrFail();
            }

            if ($lockedProduct->quantity < $quantity) {
                throw new \Exception("Insufficient stock for product: {$lockedProduct->name}");
            }

            $lockedProduct->decrement('quantity', $quantity);

            $movement = StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_key' => $resolvedMovementKey,
                'type' => 'adjustment',
                'quantity' => $quantity,
                'reason' => $reason,
                'notes' => $notes ?? "Inventory write-off: {$reason}",
            ]);

            $unitCostCents = Money::toCents((float) $lockedProduct->cost_price);
            $totalLossCents = $unitCostCents * $quantity;

            $loss = ProductLoss::create([
                'business_branch_id' => $lockedProduct->business_branch_id,
                'product_id' => $lockedProduct->id,
                'stock_movement_id' => $movement->id,
                'type' => $reason,
                'quantity' => $quantity,
                'unit_cost' => $unitCostCents,
                'total_loss' => $totalLossCents,
                'reason' => $notes,
                'loss_date' => $lossDate ?? now()->toDateString(),
                'reported_by' => auth()->id(),
            ]);

            CashFlow::create([
                'transaction_code' => 'CF-LOSS-'.str_pad((string) $loss->id, 6, '0', STR_PAD_LEFT),
                'type' => CashFlowType::Expense->value,
                'amount' => Money::fromCents($totalLossCents),
                'currency' => 'UGX',
                'business_branch_id' => $lockedProduct->business_branch_id,
                'description' => "Inventory loss ({$reason}): {$lockedProduct->name} x{$quantity}",
                'category' => 'inventory_loss',
                'status' => 'completed',
                'transaction_date' => $lossDate ?? now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            return $loss;
        });
    }
}
