<?php

namespace Database\Factories;

use App\Models\TaxCategory;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        return [
            'tax_category_id' => TaxCategory::factory(),
            'name' => fake()->unique()->words(2, true).' Rate',
            'rate' => fake()->randomFloat(4, 0.05, 0.30),
            'jurisdiction_zone' => 'Uganda',
            'is_active' => true,
        ];
    }
}
