<?php

namespace App\Services\Sync\Handlers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\Sync\SyncConflictException;
use App\Services\Sync\SyncHandler;
use App\Support\Auth\RolePermissions;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Applies a signed stock adjustment captured offline.
 *
 * The payload carries a *delta*, never an absolute balance: the device's cached
 * quantity may be arbitrarily stale, so the only trustworthy quantity is the one
 * already on the server plus an explicit change. The delta is applied through
 * InventoryService so every adjustment journals a StockMovement and locks the
 * product row — the same path the online stock-adjustment endpoint uses.
 *
 * Idempotency is two-layered: the dispatcher's SyncOperation guard covers the
 * normal replay, and the deterministic movement key (`sync:<operation_id>`) makes
 * the stock effect itself replay-safe even if a crash falls between the business
 * commit and the completion flip.
 */
class InventorySyncHandler implements SyncHandler
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function type(): string
    {
        return 'inventory';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync inventory adjustments.');
        }

        $validated = Validator::make($payload, [
            'product_id' => ['required', 'integer'],
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['nullable', Rule::in(['adjustment', 'damaged', 'expired', 'lost', 'stock_take', 'other'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        if (! RolePermissions::canModifyStock($user)) {
            throw new AuthorizationException('Your role cannot adjust stock.');
        }

        // Tenant and branch isolation come from the model's global scopes: a product
        // outside the caller's business or branch set simply does not resolve, and
        // the ModelNotFoundException is reported as `not_found`.
        $product = Product::query()
            ->whereHas('businessBranch', fn ($query) => $query->where('business_id', $user->business_id))
            ->findOrFail($validated['product_id']);

        $delta = (int) $validated['quantity_delta'];
        $requestedRemoval = $delta < 0 ? abs($delta) : 0;

        if ($requestedRemoval > (int) $product->quantity) {
            throw $this->stockConflict($product, $requestedRemoval);
        }

        try {
            $movement = $this->inventoryService->adjust(
                $product,
                $delta,
                $validated['notes'] ?? null,
                $validated['reason'] ?? 'adjustment',
                'sync:'.$user->business_id.':'.$operationId,
            );
        } catch (Exception $e) {
            // InventoryService re-checks the balance under the row lock; losing a
            // race with a concurrent sale is the same conflict, reported the same way.
            if (str_starts_with($e->getMessage(), 'Cannot reduce stock below zero')) {
                $product->refresh();
                throw $this->stockConflict($product, $requestedRemoval);
            }

            throw $e;
        }

        $movement = StockMovement::query()
            ->where('movement_key', 'sync:'.$user->business_id.':'.$operationId)
            ->firstOrFail();

        $product->refresh();

        return [
            'server_id' => (int) $product->getKey(),
            'resource_type' => 'product',
            'result' => [
                'product_id' => (int) $product->getKey(),
                'quantity' => (int) $product->quantity,
                'quantity_delta' => $delta,
                'movement_id' => (int) $movement->id,
            ],
        ];
    }

    private function stockConflict(Product $product, int $requestedRemoval): SyncConflictException
    {
        $available = (int) $product->quantity;

        return SyncConflictException::make(
            'insufficient_stock',
            "{$product->name}: Only {$available} available, but {$requestedRemoval} requested to remove.",
            [
                'product_id' => (int) $product->getKey(),
                'requested' => $requestedRemoval,
                'available' => $available,
                'shortfall' => max(0, $requestedRemoval - $available),
            ],
        );
    }
}
