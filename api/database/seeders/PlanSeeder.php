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
                'description' => 'Perfect for small and growing businesses looking for a modern inventory management solution.',
                'monthly_price' => 55000,
                'yearly_price' => 561000,
                'billing_cycle' => 'monthly',
                'discount_percentage' => 15,
                'features' => [
                    'Up to 500 products',
                    '1 business branch',
                    '2 users',
                    'Inventory management',
                    'Sales & purchase management',
                    'Customer management',
                    'Profit & loss reports',
                    'Low stock alerts',
                    'Automated WhatsApp notifications',
                    'AI Business Assistant',
                    'Email support',
                ],
                'limits' => [
                    'max_products' => 500,
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
                'description' => 'Built for established businesses managing multiple employees and growing inventory.',
                'monthly_price' => 85000,
                'yearly_price' => 816000,
                'billing_cycle' => 'monthly',
                'discount_percentage' => 20,
                'features' => [
                    'Up to 2,000 products',
                    'Up to 3 branches',
                    '8 users',
                    'Advanced inventory tracking',
                    'Sales analytics',
                    'Supplier management',
                    'Employee management',
                    'Barcode support',
                    'Profit & loss reports',
                    'Advanced AI Assistant',
                    'Automated WhatsApp notifications',
                    'Priority email support',
                ],
                'limits' => [
                    'max_products' => 2000,
                    'max_branches' => 3,
                    'max_users' => 8,
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
                'description' => 'Ideal for supermarkets, wholesalers and businesses with multiple branches.',
                'monthly_price' => 135000,
                'yearly_price' => 1215000,
                'billing_cycle' => 'monthly',
                'discount_percentage' => 25,
                'features' => [
                    'Up to 10,000 products',
                    'Up to 10 branches',
                    '25 users',
                    'Real-time inventory',
                    'Stock transfers',
                    'Purchase order management',
                    'Promotions & discounts',
                    'Advanced analytics',
                    'Forecasting reports',
                    'Advanced AI insights',
                    'Automated WhatsApp notifications',
                    'API access',
                    'Priority support',
                ],
                'limits' => [
                    'max_products' => 10000,
                    'max_branches' => 10,
                    'max_users' => 25,
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
                'description' => 'For organizations requiring unlimited scalability, dedicated support and custom integrations.',
                'monthly_price' => 250000,
                'yearly_price' => 2100000,
                'billing_cycle' => 'monthly',
                'discount_percentage' => 30,
                'features' => [
                    'Unlimited products',
                    'Unlimited branches',
                    'Unlimited users',
                    'Custom integrations',
                    'Dedicated onboarding',
                    'Advanced AI Assistant',
                    'Advanced analytics',
                    'Custom reports',
                    'Role & permission management',
                    'Multi-currency support',
                    'Tax & invoicing',
                    'API access',
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
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('✅ Seeded ' . count($plans) . ' plans successfully!');
    }
}