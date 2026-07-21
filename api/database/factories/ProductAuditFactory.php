<?php

namespace Database\Factories;

use App\Models\ProductAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAuditFactory extends Factory
{
    protected $model = ProductAudit::class;

    public function definition(): array
    {
        return [
            'audit_number' => 'PAUDIT-' . fake()->unique()->numerify('########'),
            'audit_date' => fake()->date(),
            'status' => fake()->randomElement(['draft', 'in_progress', 'completed', 'approved']),
            'notes' => fake()->sentence(),
            'performed_by' => User::factory(),
        ];
    }
}
