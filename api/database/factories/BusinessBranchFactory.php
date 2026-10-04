<?php

namespace Database\Factories;

use App\Models\BusinessBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessBranchFactory extends Factory
{
    protected $model = BusinessBranch::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Branch',
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
        ];
    }
}
