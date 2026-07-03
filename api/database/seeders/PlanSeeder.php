<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'mark' => 'Affordable',
                'description' => 'Perfect for small shops, kiosks, boutiques, and individual retailers getting started with inventory management.',
                'monthly_price' => 30000,
                'yearly_price' => 300000,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 300 products',
                    '1 business branch',
                    '2 users',
                    'Inventory management',
                    'Sales & purchase records',
                    'Customer management',
                    'Basic reports',
                    'Low stock alerts',
                    'Email support',
                ],
                'limits' => [
                    'max_products' => 300,
                    'max_branches' => 1,
                    'max_users' => 2,
                ],
                'status' => 'active',
                'is_active' => true,
                'sort_order' => 1,
                'currency' => 'UGX',
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'mark' => 'Most Popular',
                'description' => 'Ideal for growing retail shops, pharmacies, restaurants, and businesses with multiple staff.',
                'monthly_price' => 75000,
                'yearly_price' => 750000,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 1,500 products',
                    'Up to 2 business branches',
                    '5 users',
                    'Advanced inventory tracking',
                    'Sales analytics',
                    'Supplier management',
                    'Employee management',
                    'Barcode support',
                    'Profit & loss reports',
                    'Low stock notifications',
                    'WhatsApp support',
                ],
                'limits' => [
                    'max_products' => 1500,
                    'max_branches' => 2,
                    'max_users' => 5,
                ],
                'status' => 'active',
                'is_active' => true,
                'sort_order' => 2,
                'currency' => 'UGX',
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'mark' => 'Best Value',
                'description' => 'Designed for supermarkets, wholesalers, and businesses operating across multiple branches.',
                'monthly_price' => 150000,
                'yearly_price' => 1500000,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 10,000 products',
                    'Up to 10 branches',
                    '20 users',
                    'Real-time inventory sync',
                    'Stock transfers',
                    'Purchase order management',
                    'Promotions & discounts',
                    'Advanced analytics',
                    'Forecasting reports',
                    'API access',
                    'Priority support',
                ],
                'limits' => [
                    'max_products' => 10000,
                    'max_branches' => 10,
                    'max_users' => 20,
                ],
                'status' => 'active',
                'is_active' => true,
                'sort_order' => 3,
                'currency' => 'UGX',
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'mark' => 'Enterprise',
                'description' => 'Tailored for large organizations requiring unlimited scalability, dedicated support, and custom integrations.',
                'monthly_price' => 350000,
                'yearly_price' => 3500000,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Unlimited products',
                    'Unlimited branches',
                    'Unlimited users',
                    'Advanced inventory automation',
                    'Custom integrations',
                    'Dedicated onboarding',
                    'Custom reports',
                    'API access',
                    'Role & permission management',
                    'Multi-currency support',
                    'Tax & invoicing',
                    'Dedicated account manager',
                    'Phone, WhatsApp & priority support',
                ],
                'limits' => [
                    'max_products' => -1,
                    'max_branches' => -1,
                    'max_users' => -1,
                ],
                'status' => 'active',
                'is_active' => true,
                'sort_order' => 4,
                'currency' => 'UGX',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('✅ Seeded ' . count($plans) . ' plans successfully!');
    }
}
