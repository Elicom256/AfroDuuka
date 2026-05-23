<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupplierTableSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Prime Wholesale Ltd',
                'email' => 'primewholesale@example.com',
                'phone' => '+256700111222',
                'address' => 'Kampala Road, Kampala',
            ],
            [
                'name' => 'East Africa Supplies',
                'email' => 'easupplies@example.com',
                'phone' => '+256701333444',
                'address' => 'Ntinda, Kampala',
            ],
            [
                'name' => 'Global Traders Uganda',
                'email' => 'globaltraders@example.com',
                'phone' => '+256702555666',
                'address' => 'Nakasero, Kampala',
            ],
            [
                'name' => 'City Stock Providers',
                'email' => 'citystock@example.com',
                'phone' => '+256703777888',
                'address' => 'Wandegeya, Kampala',
            ],
            [
                'name' => 'Fresh Farm Distributors',
                'email' => 'freshfarm@example.com',
                'phone' => '+256704999000',
                'address' => 'Nakawa, Kampala',
            ],
        ];

        $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");
       
        $businessBranch = BusinessBranch::where("name", "Main Branch")
                          ->where("business_id", $businessId)->value("id");
        $role = Role::where("name", "supplier")
                          ->where("business_id", $businessId)->value("id");                  
        foreach ($suppliers as $supplierData) {
             $supplierCount = Supplier::with("user",  function ($q) use($businessId){
            $q->where("business_id", $businessId);
        })->count();
        $supplierCode = "SUP-" . str_pad( $supplierCount + 1, 5, "0", STR_PAD_LEFT );
      $nin = strtoupper( 'CM' . rand(10, 99) . rand(10000000, 99999999) . chr(rand(65, 90)) . chr(rand(65, 90)));
            // 1. Create User first
            $user = User::updateOrCreate(
                [
                    "email" => $supplierData["email"]
                ],
                [
                    "firstname" => explode(" ", $supplierData["name"])[0],
                    "lastname" => explode(" ", $supplierData["name"], 2)[1] ?? "",
                    "email" => $supplierData["email"],
                    "phone" => $supplierData["phone"],
                    "address" => $supplierData["address"],
                    "business_id" => $businessId,
                    "business_branch_id" => $businessBranch,
                    "role_id" => $role, // optionally set supplier role
                    "username" => strtoupper(explode("@", $supplierData["email"])[0]),
                    "password" => Hash::make("password"),
                    "status" => "active",
                    "nin" => $nin
                ]
            );
      
            // 2. Create Supplier profile
            Supplier::updateOrCreate(
                [
                    "user_id" => $user->id,
                ],
                [
                    "company_name" => $supplierData["name"],
                    "supplier_code" => $supplierCode,
                    "status" => "active",
                ]
            );
        }

        $this->command->info("✅ Seeded " . count($suppliers) . " Suppliers Successfully!");
    }
}