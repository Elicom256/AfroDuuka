<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'John Kamya',
                'email' => 'johnkamya@example.com',
                'phone' => '+256700111111',
                'address' => 'Kampala Road, Kampala',
            ],
            [
                'name' => 'Sarah Nakato',
                'email' => 'sarahnakato@example.com',
                'phone' => '+256701222222',
                'address' => 'Ntinda, Kampala',
            ],
            [
                'name' => 'Brian Ouma',
                'email' => 'brianouma@example.com',
                'phone' => '+256702333333',
                'address' => 'Nakasero, Kampala',
            ],
            [
                'name' => 'Grace Achieng',
                'email' => 'graceachieng@example.com',
                'phone' => '+256703444444',
                'address' => 'Wandegeya, Kampala',
            ],
            [
                'name' => 'Michael Ssemakula',
                'email' => 'michaelssemakula@example.com',
                'phone' => '+256704555555',
                'address' => 'Nakawa, Kampala',
            ],
        ];

        $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");

        $businessBranch = BusinessBranch::where("name", "Main Branch")
            ->where("business_id", $businessId)
            ->value("id");

        $role = Role::where("name", "customer")
            ->where("business_id", $businessId)
            ->value("id");

        foreach ($customers as $customerData) {

            // count per business (IMPORTANT: fix query logic)
            $customerCount = Customer::whereHas("user", function ($q) use ($businessId) {
                $q->where("business_id", $businessId);
            })->count();

            $customerCode = "CUST-" . str_pad($customerCount + 1, 5, "0", STR_PAD_LEFT);

            // fake NIN
            $nin = strtoupper(
                'CM' .
                rand(10, 99) .
                rand(10000000, 99999999) .
                chr(rand(65, 90)) .
                chr(rand(65, 90))
            );

            $nameParts = preg_split('/\s+/', trim($customerData["name"]), 2);

            // 1. Create User
            $user = User::updateOrCreate(
                [
                    "email" => $customerData["email"]
                ],
                [
                    "firstname" => $nameParts[0],
                    "lastname" => $nameParts[1] ?? "",
                    "email" => $customerData["email"],
                    "phone" => $customerData["phone"],
                    "address" => $customerData["address"],
                    "business_id" => $businessId,
                    "business_branch_id" => $businessBranch,
                    "role_id" => $role,
                    "username" => strtoupper(explode("@", $customerData["email"])[0]),
                    "password" => Hash::make("password"),
                    "status" => "active",
                    "nin" => $nin,
                ]
            );

            // 2. Create Customer profile
            Customer::updateOrCreate(
                [
                    "user_id" => $user->id,
                ],
                [
                    "customer_code" => $customerCode,
                    "status" => "active",
                ]
            );
        }

        $this->command->info("✅ Seeded " . count($customers) . " Customers Successfully!");
    }
}