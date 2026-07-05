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

        // =============================================
        // SUPER ADMIN (1)
        // =============================================
        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'firstname'          => 'Super',
                'lastname'           => 'Admin',
                'username'           => '@superadmin',
                'password'           => Hash::make('password'),
                'phone'              => '0781000001',
                'role_id'            => Role::where('name', "superadmin")->value('id'),
                'status'             => 'active',
                'nin'                => 'CM' . rand(10000000, 99999999),
            ]
        );
        $this->command->info("✅ Super Admin created: superadmin@gmail.com");

        // =============================================
        // SITE ADMINS (3)
        // =============================================
        $siteAdmins = [
            ['name' => 'Alex Ferguson', 'email' => 'alex@gmail.com',   'phone' => '0781000002'],
            ['name' => 'Pep Guardiola',  'email' => 'pep@gmail.com',    'phone' => '0781000003'],
            ['name' => 'Jurgen Klopp',   'email' => 'klopp@gmail.com',  'phone' => '0781000004'],
        ];

        foreach ($siteAdmins as $admin) {
            $parts = explode(' ', $admin['name']);

            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'firstname'          => $parts[0],
                    'lastname'           => $parts[1] ?? '',
                    'username'           => '@' . strtolower($parts[0]),
                    'password'           => Hash::make('password'),
                    'phone'              => $admin['phone'],
                    'role_id'            => Role::where('name', 'siteadmin')->value('id'),
                    'status'             => 'active',
                    'nin'                => 'CM' . rand(10000000, 99999999),
                ]
            );

            $this->command->info("✅ Site Admin created: {$admin['email']}");
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
        $branchId = BusinessBranch::where("business_id", $business_id)->where("name", "Main Branch")->value("id");
        $workers = [];
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
                    "business_branch_id" => $branchId,
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
           
    
        if($data){
            $this->command->info("✅ Seeded Users Successfully!");
        }else{
           $this->command->warn('⚠️ User insert/update may have failed.');
        }
    }
}