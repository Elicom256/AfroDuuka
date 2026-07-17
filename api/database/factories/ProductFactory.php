<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' ' . fake()->word(),
            'sku' => strtoupper(fake()->lexify('???-') . fake()->numerify('####')),
            'barcode' => fake()->numerify('2##########'),
            'quantity' => fake()->numberBetween(0, 100),
            'cost_price' => fake()->randomFloat(2, 500, 5000),
            'price' => fake()->randomFloat(2, 1000, 20000),
            'reorder_level' => 10,
            'status' => 'active',
        ];
    }
}
