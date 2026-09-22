<?php

namespace Database\Factories;

use App\Models\TaxCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxCategoryFactory extends Factory
{
    protected $model = TaxCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word() . ' Tax',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}