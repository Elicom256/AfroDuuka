<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService
{
    public function __construct(private ProfileService $profileService) {}

    public function createSupplier(array $data): Supplier
    {
        return $this->profileService->create($data, function ($user, $data) {
            $supplierCode = "SUP-" . str_pad(Supplier::count() + 1, 5, "0", STR_PAD_LEFT);
            return Supplier::create([
                "user_id" => $user->id,
                "supplier_code" => $supplierCode,
                "company_name" => $data["company_name"] ?? null,
                "status" => $data["status"] ?? "active",
            ]);
        });
    }

    // update
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        return $this->profileService->updateProfile($supplier->user, $data, function ($user, $data) use ($supplier) {
            // $supplierCode = "SUP-" . str_pad(Supplier::count() + 1, 5, "0", STR_PAD_LEFT);
            $supplier->update([
                "user_id" => $user->id,
                // "supplier_code" => $supplier->supplier_code,
                "company_name" => $data["company_name"] ?? null,
                "status" => $data["status"] ?? "active",
            ]);
            return $supplier->fresh();
        });
    }
}