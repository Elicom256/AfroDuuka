<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\User;
use App\Models\Worker;
use Carbon\Carbon;
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
        $workers = [];
foreach ($branches as $branch) {
        foreach ($users as $name => $data) {
            $nin = strtoupper( 'CM' . rand(10, 99) . rand(10000000, 99999999) . chr(rand(65, 90)) . chr(rand(65, 90)));

            // Get role by name + business
            $role = Role::where("business_id", $business_id)
                ->where("name", $data["role"])
                ->first();

            if (!$role) {
                continue; // or throw exception if you prefer strict mode
            }

             $baseUsername = explode("@", $data["email"])[0];
            $generatedUsername = "@" . $baseUsername . rand(11, 999);
            $parts = explode(" ", $name, 2);
                $data = User::updateOrCreate(
                [
                    "email" => $data["email"],
                ],
                [
                    "firstname" => $parts[0],
                    "lastname" => $parts[1],
                    "username" => $generatedUsername,
                    "password" => Hash::make("password"),
                    "phone" => $data['phone'],
                    "business_id" => $business_id,
                    "business_branch_id" => $branch->id,
                    "role_id" => $role->id,
                    "status" => "active",
                    "branch_powers" => "none",
                    "nin" => $nin
                ]
            );
                $work = Worker::with("user", function($q) use ($business_id){
                $q->where("business_id", $business_id);
            })->count();
                $deps = ["tech", "marketing", "managerial", "security"];
                // seed worker
                if(!in_array($data->id, $workers)){
                    $workers[] = $data->id;
                    Worker::updateOrCreate([
                    "user_id" => $data->id,
                    "employee_code" => "EMP-" . str_pad($work + 1, 5, "0", STR_PAD_LEFT),
                    "department" => $deps[array_rand($deps)],
                    // "position" => $data["position"] ?? null,
                    // "employment_type" => $data["employment_type"] ?? "full_time",
                    "salary" => rand(500000, 1200000),
                    "hire_date" => Carbon::now(),
                    // "status" => "active",
                ]);
                }

            }
           
        }
        if($data){
            $this->command->info("✅ Seeded Users Successfully!");
        }else{
           $this->command->warn('⚠️ User insert/update may have failed.');
        }
    }
}