<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function __construct(private ProfileService $profileService) {}

    public function createCustomer(array $data)
    {
        return $this->profileService->create($data, function ($user, $data) {

            return Customer::create([
                'user_id' => $user->id,
                'customer_code' => 'CUST-'.str_pad(Customer::count() + 1, 5, '0', STR_PAD_LEFT),
                'company_name' => $data['company_name'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);
        });
    }

    // update customer
    public function updateCustomer(Customer $customer, array $data)
    {
        return $this->profileService->updateProfile($customer->user, $data, function ($user, $data) use ($customer) {

            $customer->update([
                // "user_id" => $user->id,
                'company_name' => $data['company_name'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            return $customer;
        });
    }
}
