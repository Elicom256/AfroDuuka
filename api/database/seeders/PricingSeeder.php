<?php

namespace Database\Seeders;

use App\Models\Pricing;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small shops and individual retailers just getting started with inventory management.',
                'monthly_price' => 50000,
                'yearly_price' => 500000,
                'currency' => 'UGX',
                'sort_order' => 1,
                'features' => [
                    'Up to 500 products',
                    '1 business branch',
                    'Basic inventory tracking',
                    'Sales & purchase records',
                    'Customer management',
                    'Basic reports',
                    'Email support',
                ],
                'limits' => [
                    'max_products' => 500,
                    'max_branches' => 1,
                    'max_users' => 2,
                ],
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'Ideal for growing businesses with multiple branches and a larger product catalog.',
                'monthly_price' => 150000,
                'yearly_price' => 1500000,
                'currency' => 'UGX',
                'sort_order' => 2,
                'features' => [
                    'Up to 2,000 products',
                    'Up to 3 business branches',
                    'Advanced inventory tracking',
                    'Sales analytics & trends',
                    'Supplier management',
                    'Employee management',
                    'Promotions & discounts',
                    'Advanced reports',
                    'Priority email & chat support',
                ],
                'limits' => [
                    'max_products' => 2000,
                    'max_branches' => 3,
                    'max_users' => 10,
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Full-featured plan for large retailers and businesses with complex inventory needs.',
                'monthly_price' => 500000,
                'yearly_price' => 5000000,
                'currency' => 'UGX',
                'sort_order' => 3,
                'features' => [
                    'Unlimited products',
                    'Unlimited branches',
                    'Real-time inventory sync',
                    'Advanced analytics & forecasting',
                    'Loyalty program management',
                    'Multi-currency support',
                    'Tax management & invoicing',
                    'Stock transfers between branches',
                    'Reorder rules & automation',
                    'API access',
                    'Dedicated account manager',
                    'Phone, chat & priority support',
                ],
                'limits' => [
                    'max_products' => -1,
                    'max_branches' => -1,
                    'max_users' => -1,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Pricing::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
