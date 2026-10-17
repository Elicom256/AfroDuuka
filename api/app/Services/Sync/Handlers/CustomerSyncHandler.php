<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Replays an offline customer creation through the same gates as the online
 * endpoint: the customer policy, then the business-level feature setting, then
 * CustomerService. ValidatesPayload merges business/branch/role onto the payload
 * from the authenticated user, so a device cannot assign another tenant's role or
 * branch to the account it creates.
 */
class CustomerSyncHandler implements SyncHandler
{
    public function __construct(private readonly CustomerService $customerService)
    {
    }

    public function type(): string
    {
        return 'customer';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync customers.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            $customer = Customer::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('delete', $customer)) {
                throw new AuthorizationException('Your role cannot delete customers.');
            }
            $id = (int) $customer->getKey();
            $customer->delete();

            return [
                'server_id' => $id,
                'resource_type' => 'customer',
                'result' => ['id' => $id],
            ];
        }

        if ($action === 'update') {
            $customer = Customer::query()->findOrFail(SyncAction::id($payload));
            if (! $user->can('update', $customer)) {
                throw new AuthorizationException('Your role cannot update customers.');
            }
            $validated = ValidatesPayload::run(UpdateCustomerRequest::class, SyncAction::body($payload));
            $updated = $this->customerService->updateCustomer($customer, $validated);

            return [
                'server_id' => (int) $updated->getKey(),
                'resource_type' => 'customer',
                'result' => [
                    'id' => (int) $updated->getKey(),
                    'customer_code' => $updated->customer_code,
                ],
            ];
        }

        $validated = ValidatesPayload::run(StoreCustomerRequest::class, SyncAction::body($payload));

        if (empty($validated['phone'])) {
            throw ValidationException::withMessages([
                'phone' => 'A phone number is required to create a customer.',
            ]);
        }

        if (! empty($validated['business_branch_id']) && ! BusinessBranch::query()
            ->whereKey($validated['business_branch_id'])
            ->where('business_id', $user->business_id)
            ->exists()) {
            throw new AuthorizationException('The selected branch is not part of your business.');
        }

        if (! $user->can('create', Customer::class)) {
            throw new AuthorizationException('Your role cannot create customers.');
        }

        if (CustomersSettings::value('status') !== 'enabled') {
            throw new AuthorizationException('Customer creation is disabled.');
        }

        $customer = $this->customerService->createCustomer($validated);

        return [
            'server_id' => (int) $customer->getKey(),
            'resource_type' => 'customer',
            'result' => [
                'id' => (int) $customer->getKey(),
                'customer_code' => $customer->customer_code,
            ],
        ];
    }
}
