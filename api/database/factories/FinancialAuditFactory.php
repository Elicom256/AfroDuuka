<?php

namespace Database\Factories;

use App\Models\FinancialAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialAuditFactory extends Factory
{
    protected $model = FinancialAudit::class;

    public function definition(): array
    {
        $expected = fake()->randomFloat(2, 1000, 100000);
        $actual = fake()->randomFloat(2, 1000, 100000);
        return [
            'audit_number' => 'FAUDIT-' . fake()->unique()->numerify('########'),
            'audit_date' => fake()->date(),
            'expected_balance' => $expected,
            'actual_balance' => $actual,
            'difference' => $actual - $expected,
            'status' => fake()->randomElement(['draft', 'in_progress', 'completed', 'approved']),
            'notes' => fake()->sentence(),
            'performed_by' => User::factory(),
        ];
    }
}
