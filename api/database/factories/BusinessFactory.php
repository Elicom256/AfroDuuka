<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'business_category_id' => BusinessCategory::factory(),
            'country_id' => Country::factory(),
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
