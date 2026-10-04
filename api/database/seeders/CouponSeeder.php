<?php

namespace Database\Seeders;

use App\Models\BusinessBranch;
use App\Models\Coupon;
use Database\Seeders\Concerns\SeedsFixtureBusiness;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    use SeedsFixtureBusiness;

    public function run(): void
    {
        $business = $this->fixtureBusiness();

        $branch = BusinessBranch::where('business_id', $business->id)
            ->where('name', 'Main Branch')
            ->first()
                   ?? $this->fixtureMainBranch($business);

        $coupons = [
            [
                'code' => 'TEST001',
                'description' => '10% off on all electronics',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_order_amount' => 50000,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addMonths(1)->toDateString(),
                'max_uses' => 100,
                'used_count' => 5,
                'status' => 'active',
            ],
            [
                'code' => 'TEST002',
                'description' => 'Flat 20,000 off on purchases above 200,000',
                'discount_type' => 'fixed',
                'discount_value' => 20000,
                'min_order_amount' => 200000,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addMonths(2)->toDateString(),
                'max_uses' => 50,
                'used_count' => 12,
                'status' => 'active',
            ],
            [
                'code' => 'TEST003',
                'description' => '15% discount for new customers',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_order_amount' => 0,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addMonths(3)->toDateString(),
                'max_uses' => 200,
                'used_count' => 0,
                'status' => 'active',
            ],
            [
                'code' => 'TEST004',
                'description' => 'Expired flash sale coupon',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'min_order_amount' => 100000,
                'valid_from' => now()->subMonths(2)->toDateString(),
                'valid_until' => now()->subMonth()->toDateString(),
                'max_uses' => 50,
                'used_count' => 30,
                'status' => 'expired',
            ],
        ];

        foreach ($coupons as $data) {
            Coupon::updateOrCreate(
                ['code' => $data['code'], 'business_id' => $business->id],
                [
                    'business_branch_id' => $branch->id,
                    ...$data,
                ]
            );
        }

        $this->command->info('✅ Coupons seeded successfully!');
    }
}
