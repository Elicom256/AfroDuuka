<?php

namespace Database\Seeders;

use App\Models\EmployeeSalary;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class EmployeeSalarySeeder extends Seeder
{
    public function run(): void
    {
        $workers = Worker::whereHas('user', fn ($q) => $q->where('role_id', '!=', 1))->get();

        if ($workers->isEmpty()) {
            if (EmployeeSalary::exists()) {
                return;
            }

            EmployeeSalary::factory()->count(5)->create();

            return;
        }

        foreach ($workers as $worker) {
            if (EmployeeSalary::where('worker_id', $worker->id)->exists()) {
                continue;
            }

            EmployeeSalary::factory()->create([
                'worker_id' => $worker->id,
                'amount' => fake()->numberBetween(500000, 5000000),
                'currency' => 'UGX',
                'effective_date' => now()->startOfMonth(),
                'status' => 'active',
            ]);
        }
    }
}
