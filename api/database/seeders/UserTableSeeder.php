<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get business ID safely
        $business_id = Business::where("email", "testbusinessone@gmail.com")
            ->value("id");

        if (!$business_id) {
            throw new \Exception("Business not found");
        }

        // Define users with role names (clean mapping)
        $users = [
            "Mikel Arteta" => [
                "email" => "admin@gmail.com",
                "role" => "admin",
                 "phone" => "0781490833"
            ],
            "Martin Odegaard" => [
                "email" => "odegaard@gmail.com",
                "role" => "manager",
                "phone" => "0781490811"
            ],
            "Bukayo Saka" => [
                "email" => "saka@gmail.com",
                "role" => "editor",
                "phone" => "0781490800"
            ],
            "Declan Rice" => [
                "email" => "rice@gmail.com",
                "role" => "staff",
                "phone" => "0781490822"
            ],
        ];

        $branches = BusinessBranch::where("business_id", $business_id)->get();
foreach ($branches as $branch) {
        foreach ($users as $name => $data) {
            // Get role by name + business
            $role = Role::where("business_id", $business_id)
                ->where("name", $data["role"])
                ->first();

            if (!$role) {
                continue; // or throw exception if you prefer strict mode
            }

            $username = strtolower(str_replace(' ', '_', $name));

            
                $data = User::updateOrCreate(
                [
                    "email" => $data["email"],
                ],
                [
                    "name" => $name,
                    "username" => "@" . $username,
                    "password" => Hash::make("password"),
                    "phone" => $data['phone'],
                    "business_id" => $business_id,
                    "business_branch_id" => $branch->id,
                    "role_id" => $role->id,

                ]
            );
            }
           
        }
        if($data){
            $this->command->info("✅ Seeded Users Successfully!");
        }else{
           $this->command->warn('⚠️ User insert/update may have failed.');
        }
    }
}