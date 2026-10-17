<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreSaleReturnRequest;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\SaleReturnService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use App\Support\Auth\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Replays a sale return through SaleReturnService, the same path as the online
 * store and update. Delete stays refused: the online controller answers 405.
 */
class SaleReturnSyncHandler implements SyncHandler
{
    public function __construct(private readonly SaleReturnService $saleReturns)
    {
    }

    public function type(): string
    {
        return 'sale_return';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync sale returns.');
        }

        if (! RolePermissions::canModifyStock($user)) {
            throw new AuthorizationException('You cannot process sale returns.');
        }

        $action = SyncAction::name($payload);
        $validated = ValidatesPayload::run(StoreSaleReturnRequest::class, SyncAction::body($payload));

        if ($action === 'delete') {
            throw ValidationException::withMessages([
                'action' => 'Sale returns cannot be deleted. Update the return to correct it instead.',
            ]);
        }

        if ($action === 'update') {
            $saleReturn = SaleReturn::query()->findOrFail(SyncAction::id($payload));
            $updated = $this->saleReturns->handleUpdateSaleReturn($saleReturn, $validated);

            return SyncAction::result('sale_return', (int) $updated->getKey());
        }

        $created = $this->saleReturns->handleCreateSaleReturn($validated, $user->business_branch_id);

        return SyncAction::result('sale_return', (int) $created->getKey());
    }
}
