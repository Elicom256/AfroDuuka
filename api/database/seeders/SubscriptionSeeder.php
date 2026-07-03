<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = Business::where('email', 'testbusinessone@gmail.com')->value('id');
        $planIds = Plan::pluck('id')->toArray();
        $statuses = ['active', 'paused', 'cancelled', 'expired'];

        if (!$businessId || empty($planIds)) {
            return;
        }

        for ($i = 1; $i <= 10; $i++) {
            $subscription = [
                'business_id' => $businessId,
                'plan_id' => $planIds[array_rand($planIds)],
                'status' => $i === 10 ? 'active' : $statuses[array_rand($statuses)],
                'starts_at' => now()->subDays(rand(1, 365)),
                'ends_at' => now()->addDays(rand(30, 365)),
                'trial_ends_at' => now()->addDays(14),
            ];

            Subscription::updateOrCreate(
                ['business_id' => $subscription['business_id'], 'plan_id' => $subscription['plan_id']],
                $subscription
            );
        }
    }
}
