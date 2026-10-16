<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerService;
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

        $validated = ValidatesPayload::run(StoreCustomerRequest::class, $payload);

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
