<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\User;
use App\Services\PurchaseReturnService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use App\Support\Auth\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Replays a purchase return through PurchaseReturnService. Stock and the refund
 * are applied there, so this handler does not recompute either.
 */
class PurchaseReturnSyncHandler implements SyncHandler
{
    public function __construct(private readonly PurchaseReturnService $purchaseReturns)
    {
    }

    public function type(): string
    {
        return 'purchase_return';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync purchase returns.');
        }

        if (! RolePermissions::canModifyStock($user)) {
            throw new AuthorizationException('You cannot process purchase returns.');
        }

        $validated = ValidatesPayload::run(StorePurchaseReturnRequest::class, SyncAction::body($payload));
        $created = $this->purchaseReturns->handleCreatePurchaseReturn($validated);

        return SyncAction::result('purchase_return', (int) $created->getKey());
    }
}
