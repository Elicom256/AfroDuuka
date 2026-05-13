<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;
use App\Models\BusinessBranch;

class BusinessBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                $business = Business::where("email", "testbusinessone@gmail.com")->first();

        $branches = [
            [
                'name' => 'Main Branch',
                'address' => 'Kampala Road, Kampala',
            ],
            [
                'name' => 'Ntinda Branch',
                'address' => 'Ntinda Trading Center, Kampala',
            ],
            [
                'name' => 'Entebbe Branch',
                'address' => 'Entebbe Road, Entebbe',
            ],
            [
                'name' => 'Mbarara Branch',
                'address' => 'High Street, Mbarara',
            ],
        ];

        foreach ($branches as $branch) {
            BusinessBranch::updateOrCreate(["name" => $branch['name']], [...$branch, "business_id" => $business->id]);
        }
        $this->command->info("✅ Seeded " . count($branches) . " Branches Successfully!");
    }
}