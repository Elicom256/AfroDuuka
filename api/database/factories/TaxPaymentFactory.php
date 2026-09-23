<?php

namespace Database\Factories;

use App\Models\TaxCategory;
use App\Models\TaxPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxPaymentFactory extends Factory
{
    protected $model = TaxPayment::class;

    public function definition(): array
    {
        return [
            'business_branch_id' => null,
            'tax_category_id' => TaxCategory::factory(),
            'amount' => fake()->randomFloat(2, 100_000, 2_000_000),
            'payment_date' => now()->subDays(fake()->numberBetween(0, 90))->toDateString(),
            'tax_period_start' => now()->startOfMonth()->toDateString(),
            'tax_period_end' => now()->endOfMonth()->toDateString(),
            'reference' => 'REF-' . strtoupper(fake()->bothify('####')),
            'notes' => fake()->sentence(),
        ];
    }
}