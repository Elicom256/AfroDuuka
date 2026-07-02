<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = [1];
        $pricings = [1, 2, 3];
        $statuses = ['active', 'inactive', 'terminated'];

        foreach ($businesses as $businessId) {
            foreach ($pricings as $pricingId) {
                $status = $statuses[array_rand($statuses)];

                $plan = Plan::updateOrCreate(
                    ['business_id' => $businessId, 'pricing_id' => $pricingId],
                    [
                        'status' => $status,
                    ]
                );
            }
        }
    }
}
