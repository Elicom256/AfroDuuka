<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerService
{
    public function __construct(private ProfileService $profileService) {}

    public function createCustomer(array $data)
    {
        return $this->profileService->create($data, function ($user, $data) {

            return Customer::create([
                "user_id" => $user->id,
                "customer_code" => "CUST-" . str_pad(Customer::count() + 1, 5, "0", STR_PAD_LEFT),
                "company_name" => $data["company_name"] ?? null,
                "remarks" => $data["remarks"] ?? null,
                "status" => $data["status"] ?? "active",
            ]);
        });
    }
}