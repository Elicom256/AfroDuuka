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

        $remunerations = [
            ['type' => 'salary',  'amount' => 2500000, 'payment_date' => now()->subMonths(2)->startOfMonth(), 'reference' => 'SAL-01', 'status' => 'paid',  'description' => 'Monthly salary payment'],
            ['type' => 'salary',  'amount' => 2500000, 'payment_date' => now()->subMonth()->startOfMonth(),   'reference' => 'SAL-02', 'status' => 'paid',  'description' => 'Monthly salary payment'],
            ['type' => 'bonus',   'amount' => 200000,  'payment_date' => now(),                               'reference' => 'BON',    'status' => 'pending', 'description' => 'Performance bonus'],
        ];

        foreach ($workers as $index => $worker) {
            foreach ($remunerations as $rem) {
                EmployeeRemuneration::create([
                    'worker_id' => $worker->id,
                    'business_id' => $businessId,
                    'business_branch_id' => $branchId,
                    'type' => $rem['type'],
                    'amount' => $rem['amount'] + ($rem['type'] === 'salary' ? $index * 250000 : 0),
                    'payment_date' => $rem['payment_date'],
                    'reference' => $rem['reference'] . '-' . $worker->id,
                    'status' => $rem['status'],
                    'description' => $rem['description'],
                ]);
            }
        }
    }
}