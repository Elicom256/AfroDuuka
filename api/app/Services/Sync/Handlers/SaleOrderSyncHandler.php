<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreSaleOrderRequest;
use App\Http\Requests\UpdateSaleOrderRequest;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\User;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use App\Support\Auth\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Replays a sale order with the same numbering and line writes as
 * SaleOrderController. There is no order service; this is that controller's
 * transaction, scoped to the authenticated business.
 */
class SaleOrderSyncHandler implements SyncHandler
{
    public function type(): string
    {
        return 'order';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync orders.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            $order = SaleOrder::query()->findOrFail(SyncAction::id($payload));
            $id = (int) $order->getKey();
            $order->items()->delete();
            $order->delete();

            return SyncAction::result('order', $id);
        }

        if ($action === 'update') {
            if (! RolePermissions::canManageBranch($user)) {
                throw new AuthorizationException('You cannot edit sale orders.');
            }
            $order = SaleOrder::query()->findOrFail(SyncAction::id($payload));
            $validated = ValidatesPayload::run(UpdateSaleOrderRequest::class, SyncAction::body($payload));
            $order->update($validated);

            return SyncAction::result('order', (int) $order->getKey());
        }

        if (! RolePermissions::canManageBranch($user)) {
            throw new AuthorizationException('You cannot create sale orders.');
        }

        $validated = ValidatesPayload::run(StoreSaleOrderRequest::class, SyncAction::body($payload));

        $order = DB::transaction(function () use ($validated, $user) {
            $orderCount = SaleOrder::query()->where('business_id', $user->business_id)->count();
            $orderNumber = 'ORD-'.str_pad((string) ($orderCount + 1), 6, '0', STR_PAD_LEFT);
            $totalAmount = collect($validated['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

            $order = SaleOrder::create([
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                SaleOrderItem::create([
                    'sale_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'allocated_qty' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order;
        });

        return SyncAction::result('order', (int) $order->getKey(), [
            'order_number' => $order->order_number,
        ]);
    }
}
