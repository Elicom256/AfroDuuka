<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'mark' => 'Affordable',
                'description' => 'A simple starting point for small shops that need reliable stock and sales control.',
                'monthly_price' => 50000,
                'yearly_price' => 500000,
                'billing_cycle' => 'monthly',
                'discount_percentage' => 0,
                'features' => [
                    'Up to 500 products',
                    '1 business branch',
                    '3 users',
                    'Inventory management',
                    'Sales & purchase management',
                    'Customer management',
                    'Profit & loss reports',
                    'Low stock alerts',
                    'Automated WhatsApp notifications',
                    'AI business assistant',
                    'Email support',
                ],
                'limits' => [
                    'max_products' => 500,
                    'max_branches' => 1,
                    'max_users' => 3,
                ],
                'status' => 'active',
                'is_active' => true,
                'sort_order' => 1,
                'currency' => 'UGX',
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'mark' => 'Most Popular',
                'description' => 'For established businesses that need deeper reporting and control across their teams.',
                'monthly_price' => 100000,
                'yearly_price' => 1000000,
                'billing_cycle' => 'monthly',
                'discount_percentage' => 0,
                'features' => [
                    'Up to 5,000 products',
                    'Up to 5 branches',
                    '15 users',
                    'Advanced inventory tracking',
                    'Sales analytics',
                    'Supplier management',
                    'Employee management',
                    'Barcode support',
                    'Profit & loss reports',
                    'Advanced AI assistant',
                    'Automated WhatsApp notifications',
                    'Priority email support',
                ],
                'limits' => [
                    'max_products' => 5000,
                    'max_branches' => 5,
                    'max_users' => 15,
                ],
                'status' => 'active',
                'is_active' => true,
                'sort_order' => 2,
                'currency' => 'UGX',
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'mark' => 'Best Value',
                'description' => 'For larger organizations that need unlimited scale, integrations and hands-on support.',
                'monthly_price' => 200000,
                'yearly_price' => 2000000,
                'billing_cycle' => 'monthly',
                'discount_percentage' => 0,
                'features' => [
                    'Unlimited products',
                    'Unlimited branches',
                    'Unlimited users',
                    'Custom integrations',
                    'Dedicated onboarding',
                    'Advanced AI assistant',
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
                'sort_order' => 3,
                'currency' => 'UGX',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $legacyPlanMap = [
            'starter' => 'basic',
            'essentials' => 'basic',
            'business' => 'pro',
            'growth' => 'pro',
            'professional' => 'pro',
        ];

        foreach ($legacyPlanMap as $legacySlug => $currentSlug) {
            $legacyPlan = Plan::where('slug', $legacySlug)->first();
            $currentPlan = Plan::where('slug', $currentSlug)->first();

            if ($legacyPlan && $currentPlan) {
                Subscription::where('plan_id', $legacyPlan->id)
                    ->update(['plan_id' => $currentPlan->id]);
                $legacyPlan->delete();
            }
        }
    }
}
