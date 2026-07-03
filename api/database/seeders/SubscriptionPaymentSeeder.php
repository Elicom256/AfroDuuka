<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = PaymentMethod::pluck('id')->toArray();
        $subscriptions = Subscription::all();

        if ($subscriptions->isEmpty() || empty($paymentMethods)) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            SubscriptionPayment::updateOrCreate(
                [
                    'subscription_id' => $subscription->id,
                    'transaction_id' => 'txn_' . $subscription->id,
                ],
                [
                    'payment_method_id' => $paymentMethods[array_rand($paymentMethods)],
                    'amount_paid' => rand(30000, 350000),
                    'transaction_id' => 'txn_' . $subscription->id,
                    'number_paid' => '255' . rand(700000000, 799999999),
                    'payment_status' => 'completed',
                    'payment_proof' => null,
                    'verified_by' => null,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                    'notes' => 'Seeded subscription payment',
                ]
            );
        }
    }
}
