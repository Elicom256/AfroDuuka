<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->country(),
            'iso_alpha2' => strtoupper(fake()->unique()->lexify('??')),
            'currency_code' => fake()->currencyCode(),
            'currency_symbol' => '$',
            'default_vat_rate' => 0.00,
        ];
    }
}