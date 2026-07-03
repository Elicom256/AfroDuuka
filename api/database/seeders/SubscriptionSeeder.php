<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscribers = [];
                $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");


        for ($i = 1; $i <= 10; $i++) {
            $subscribers[] = [
                'business_id' => $businessId,
                'plan_id' => rand(1, 3),
                'payment_method_id' => rand(1, 3),
                'status' => $i === 10 ? 'active' : 'cancelled',
                'start_date' => now()->subDays(rand(1, 365)),
                'end_date' => now()->addDays(rand(30, 365)),
                'trial_ends_at' => now()->addDays(14),
            ];
        }

        foreach ($subscribers as $subscriber) {
            Subscription::updateOrCreate(
                ['business_id' => $subscriber['business_id'], 'plan_id' => $subscriber['plan_id']],
                $subscriber
            );
        }
    }
}
