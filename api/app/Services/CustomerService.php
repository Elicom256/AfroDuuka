<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerService
{
    public function customer(array $validated): Customer
    {
        return DB::transaction(function () use ($validated) {

            $baseUsername = explode("@", $validated["email"])[0];
            $generatedUsername = "@" . $baseUsername . rand(11, 999);
            $customerCount = Customer::where("business_id", $validated["business_id"])->count();
             // Generate customer code
            $customerCode = "CUST-" . str_pad( $customerCount + 1, 5, "0", STR_PAD_LEFT );


            $user = User::create([
                "firstname" => $validated["firstname"],
                "lastname" => $validated["lastname"],
                "email" => $validated["email"],
                "username" => strtolower($generatedUsername) ?? null,
                "password" => Hash::make($validated["password"] ?? "password"),
                "phone" => $validated["phone"] ?? null,
                "nin" => $validated["nin"] ?? null,
                "address" => $validated["address"] ?? null,

                "business_id" => $validated["business_id"],
                "business_branch_id" => $validated["business_branch_id"],
                "role_id" => $validated["role_id"],

                "branch_powers" => $validated["branch_powers"] ?? "none",
                "status" => $validated["status"] ?? "active",
            ]);

            $customer = Customer::create([
                "user_id" => $user->id,
                "customer_code" => $customerCode,
                "company_name" => $validated["company_name"] ?? null,
                "contact_person" => $validated["contact_person"] ?? null,
                "remarks" => $validated["remarks"] ?? null,
                "status" => $validated["status"] ?? "active",
            ]);

            return $customer->load("user");
        });
    }
}