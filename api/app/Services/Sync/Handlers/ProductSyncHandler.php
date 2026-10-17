<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Replays product catalogue writes. Stock quantity is not applied from an
 * update: the device's absolute count can be stale, and a stock change has to
 * arrive as an inventory operation with a signed delta.
 */
class ProductSyncHandler implements SyncHandler
{
    public function type(): string
    {
        return 'product';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync products.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            $product = Product::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('delete', $product)) {
                throw new AuthorizationException('Your role cannot delete products.');
            }
            $id = (int) $product->getKey();
            $product->delete();

            return SyncAction::result('product', $id);
        }

        if ($action === 'update') {
            $product = Product::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('update', $product)) {
                throw new AuthorizationException('Your role cannot update products.');
            }

            $validated = ValidatesPayload::run(UpdateProductRequest::class, SyncAction::body($payload));
            // The device's absolute quantity is not applied. A stock change has to
            // arrive as an inventory operation with a signed delta.
            $stockOnly = array_key_exists('quantity', $validated)
                && count(array_diff(array_keys($validated), ['quantity', 'adjustment_notes', 'adjustment_reason'])) === 0;
            unset($validated['quantity'], $validated['adjustment_notes'], $validated['adjustment_reason']);
            if ($stockOnly || $validated === []) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock changes must be sent as an inventory adjustment with a signed quantity_delta.',
                ]);
            }

            if (isset($validated['change_reason'])) {
                $product->priceChangeReason = $validated['change_reason'];
            }

            $product->update($validated);

            return SyncAction::result('product', (int) $product->getKey());
        }

        if (! $user->can('create', Product::class)) {
            throw new AuthorizationException('Your role cannot create products.');
        }

        $validated = ValidatesPayload::run(StoreProductRequest::class, SyncAction::body($payload));
        $product = Product::create($validated);

        return SyncAction::result('product', (int) $product->getKey());
    }
}
