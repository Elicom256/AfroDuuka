<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Plan;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Pricing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $businesses = [1];
        $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");

        $pricings = Pricing::all();
        $statuses = ['active', 'inactive', 'terminated'];

        // foreach ($businesses as $businessId) {
            foreach ($pricings as $pricing) {
                $status = $statuses[array_rand($statuses)];

                Plan::updateOrCreate(
                    ['business_id' => $businessId, 'pricing_id' => $pricing->id],
                    [
                        'status' => $status,
                    ]
                );
            }
            $num = count($pricings);
            $this->command->info("✅ Seeded $num Subscription plans successfully!");
        // }
    }
}
