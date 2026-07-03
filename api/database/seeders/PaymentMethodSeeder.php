<?php

namespace Database\Seeders;

use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'business_id' => 1,
                'method' => 'mobile_money',
                'status' => 'enabled',
            ],
            [
                'business_id' => 1,
                'method' => 'card',
                'status' => 'enabled',
            ],
            [
                'business_id' => 1,
                'method' => 'cash',
                'status' => 'disabled',
            ],
            [
                'business_id' => 1,
                'method' => 'credit',
                'status' => 'enabled',
            ],
            [
                'business_id' => 1,
                'method' => 'cryptocurrency',
                'status' => 'disabled',
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['business_id' => $method['business_id'], 'method' => $method['method']],
                $method
            );
        }
    }
}
