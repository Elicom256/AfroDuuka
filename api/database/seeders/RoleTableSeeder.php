<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Get business safely
        $business = Business::where("email", "testbusinessone@gmail.com")->first();

        if (!$business) {
            throw new \Exception("Business not found");
        }

        $roles = [ "admin", "manager", "editor", "staff", "worker",];

        foreach ($roles as $roleName) {
            Role::updateOrCreate(
                [
                    "name" => $roleName,
                    "business_id" => $business->id,
                ],
                []
            );
                $this->command->info("✅ Seeded " . $roleName . " Successfully!");
        }
    }
}
