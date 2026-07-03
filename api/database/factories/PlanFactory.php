<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(),
            'mark' => $this->faker->randomElement(['Affordable', 'Most Popular', 'Best Value', 'Enterprise']),
            'description' => $this->faker->sentence(12),
            'monthly_price' => $this->faker->randomFloat(2, 0, 500000),
            'yearly_price' => $this->faker->randomFloat(2, 0, 5000000),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'features' => ['Basic support', 'Reporting', 'Product limits'],
            'limits' => ['max_products' => 1000, 'max_branches' => 2, 'max_users' => 5],
            'status' => 'active',
            'is_active' => true,
            'sort_order' => 1,
            'currency' => 'UGX',
        ];
    }
}
