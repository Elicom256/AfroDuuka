<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SupplierService
{
    public function createSupplier(array $validated): Supplier
    {
        return DB::transaction(function () use ($validated) {

            $baseUsername = explode("@", $validated["email"])[0];
            $generatedUsername = "@" . $baseUsername . rand(11, 999);
            $supplierCount = Supplier::where("business_id", $validated["business_id"])->count();
             // Generate customer code
            $supplierCode = "SUP-" . str_pad( $supplierCount + 1, 5, "0", STR_PAD_LEFT );

            $user = User::create([
                "firstname" => $validated["firstname"],
                "lastname" => $validated["lastname"],
                "email" => $validated["email"],
                "username" => $validated["username"] ?? $generatedUsername,
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

            $supplier = Supplier::create([
                "user_id" => $user->id,
                "supplier_code" => $supplierCode,
                "company_name" => $validated["company_name"] ?? null,
                "status" => $validated["status"] ?? "active",
            ]);

            return $supplier->load("user");
        });
    }
}