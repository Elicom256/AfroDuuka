<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\EmployeeRemuneration;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class EmployeeRemunerationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessId = Business::where('email', 'testbusinessone@gmail.com')->value('id');

        $branchId = BusinessBranch::where('business_id', $businessId)
            ->where('name', 'Main Branch')
            ->value('id');

        $workers = Worker::with('user')
            ->whereHas('user', function ($q) use ($businessId, $branchId) {
                $q->where('business_id', $businessId)
                    ->where('business_branch_id', $branchId);
            })
            ->take(4)
            ->get();

        foreach ($workers as $index => $worker) {
            EmployeeRemuneration::updateOrCreate(
                [
                    'worker_id' => $worker->id,
                    'type' => 'salary',
                    'payment_date' => now()->subMonths(2)->startOfMonth(),
                ],
                [
                    'amount' => 2500000 + ($index * 250000),
                    'reference' => 'SAL-' . $worker->id . '-01',
                    'status' => 'paid',
                    'description' => 'Monthly salary payment',
                ]
            );

            EmployeeRemuneration::updateOrCreate(
                [
                    'worker_id' => $worker->id,
                    'type' => 'salary',
                    'payment_date' => now()->subMonth()->startOfMonth(),
                ],
                [
                    'amount' => 2500000 + ($index * 250000),
                    'reference' => 'SAL-' . $worker->id . '-02',
                    'status' => 'paid',
                    'description' => 'Monthly salary payment',
                ]
            );

            EmployeeRemuneration::updateOrCreate(
                [
                    'worker_id' => $worker->id,
                    'type' => 'bonus',
                    'payment_date' => now(),
                ],
                [
                    'amount' => 200000,
                    'reference' => 'BON-' . $worker->id,
                    'status' => 'pending',
                    'description' => 'Performance bonus',
                ]
            );
        }
    }
}