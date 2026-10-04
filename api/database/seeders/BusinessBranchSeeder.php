<?php

namespace Database\Seeders;

use App\Models\BusinessBranch;
use Database\Seeders\Concerns\SeedsFixtureBusiness;
use Illuminate\Database\Seeder;

class BusinessBranchSeeder extends Seeder
{
    use SeedsFixtureBusiness;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = $this->fixtureBusiness();

        $branches = [
            [
                'name' => 'Main Branch',
                'address' => 'Kampala Road, Kampala',
                'phone' => '0780000000',
            ],
            [
                'name' => 'Ntinda Branch',
                'address' => 'Ntinda Trading Center, Kampala',
                'phone' => '0781000000',
            ],
            [
                'name' => 'Entebbe Branch',
                'address' => 'Entebbe Road, Entebbe',
                'phone' => '0782000000',
            ],
            [
                'name' => 'Mbarara Branch',
                'address' => 'High Street, Mbarara',
                'phone' => '0783000000',
            ],
        ];

        foreach ($branches as $branch) {
            BusinessBranch::updateOrCreate(
                ['business_id' => $business->id, 'name' => $branch['name']],
                [...$branch, 'business_id' => $business->id]
            );
        }
        $this->command->info('✅ Seeded '.count($branches).' Branches Successfully!');
    }
}
