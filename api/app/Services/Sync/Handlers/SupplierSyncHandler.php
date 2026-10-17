<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\CoreSettings\SuppliersSettings;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use Illuminate\Auth\Access\AuthorizationException;

class SupplierSyncHandler implements SyncHandler
{
    public function __construct(private readonly SupplierService $suppliers)
    {
    }

    public function type(): string
    {
        return 'supplier';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync suppliers.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            $supplier = Supplier::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('delete', $supplier)) {
                throw new AuthorizationException('Your role cannot delete suppliers.');
            }
            $id = (int) $supplier->getKey();
            $supplier->delete();

            return SyncAction::result('supplier', $id);
        }

        if ($action === 'update') {
            $supplier = Supplier::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('update', $supplier)) {
                throw new AuthorizationException('Your role cannot update suppliers.');
            }
            $validated = ValidatesPayload::run(UpdateSupplierRequest::class, SyncAction::body($payload));
            $updated = $this->suppliers->updateSupplier($supplier, $validated);

            return SyncAction::result('supplier', (int) $updated->getKey());
        }

        if (! $user->can('create', Supplier::class)) {
            throw new AuthorizationException('Your role cannot create suppliers.');
        }

        if (SuppliersSettings::value('status') !== 'enabled') {
            throw new AuthorizationException('Supplier creation is disabled.');
        }

        $validated = ValidatesPayload::run(StoreSupplierRequest::class, SyncAction::body($payload));
        $supplier = $this->suppliers->createSupplier($validated);

        return SyncAction::result('supplier', (int) $supplier->getKey(), [
            'supplier_code' => $supplier->supplier_code,
        ]);
    }
}
