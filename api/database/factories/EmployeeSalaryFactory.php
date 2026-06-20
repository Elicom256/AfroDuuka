<?php

namespace Database\Factories;

use App\Models\EmployeeSalary;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeSalaryFactory extends Factory
{
    protected $model = EmployeeSalary::class;

    public function definition(): array
    {
        return [
            'worker_id' => Worker::factory(),
            'amount' => fake()->numberBetween(500000, 5000000),
            'currency' => 'UGX',
            'effective_date' => fake()->date(),
            'end_date' => null,
            'status' => 'active',
        ];
    }
}
