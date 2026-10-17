<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use App\Support\Auth\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Replays an offline purchase through PurchaseService.
 *
 * Create and receive are the writes the online controller actually performs.
 * Update and delete are refused there (a received purchase has already moved
 * stock and cost), so they are refused here too rather than replayed as a
 * last-write-wins edit of money or stock.
 */
class PurchaseSyncHandler implements SyncHandler
{
    public function __construct(private readonly PurchaseService $purchases)
    {
    }

    public function type(): string
    {
        return 'purchase';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync purchases.');
        }

        if (! RolePermissions::canCreatePurchaseOrder($user)) {
            throw new AuthorizationException('You cannot record purchases.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'update' || $action === 'delete') {
            throw ValidationException::withMessages([
                'action' => 'Purchases cannot be edited or deleted after creation.',
            ]);
        }

        if ($action === 'receive') {
            $purchase = Purchase::query()->findOrFail(SyncAction::id($payload));
            $body = SyncAction::body($payload);
            $received = $this->purchases->receivePurchase(
                $purchase,
                $body['items'] ?? [],
                $user->id,
                $body['note'] ?? null,
            );

            return SyncAction::result('purchase', (int) $received->getKey());
        }

        $validated = ValidatesPayload::run(StorePurchaseRequest::class, SyncAction::body($payload));
        $purchase = $this->purchases->savePurchase($validated);

        return SyncAction::result('purchase', (int) $purchase->getKey(), [
            'supplier_id' => $purchase->supplier_id,
        ]);
    }
}
