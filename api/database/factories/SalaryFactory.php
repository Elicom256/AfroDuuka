<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Role;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryFactory extends Factory
{
    protected $model = Salary::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'business_branch_id' => null,
            'role_id' => Role::factory(),
            'amount' => fake()->numberBetween(500000, 5000000),
            'period' => 'monthly',
            'status' => 'active',
            'set_by' => null,
        ];
    }

    public function yearly(): static
    {
        return $this->state(fn () => ['period' => 'yearly']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    public function setBy(User $user): static
    {
        return $this->state(fn () => ['set_by' => $user->id]);
    }
}
