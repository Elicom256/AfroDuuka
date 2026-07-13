<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('email', 'testbusinessone@gmail.com')->first();
        $mainBranch = BusinessBranch::where('business_id', $business->id)
            ->where('name', 'Main Branch')
            ->value('id');
        $ntindaBranch = BusinessBranch::where('business_id', $business->id)
            ->where('name', 'Ntinda Branch')
            ->value('id');

        $categories = ExpenseCategory::where('business_id', $business->id)->pluck('id', 'name');

        $expenses = [
            [
                'expense_category_id' => $categories['Utilities'],
                'amount' => 450000,
                'business_branch_id' => $mainBranch,
                'vendor' => 'Umeme Ltd',
                'description' => 'Monthly electricity bill for Main Branch',
                'payment_date' => now()->subDays(5),
                'status' => 'approved',
            ],
            [
                'expense_category_id' => $categories['Office Supplies'],
                'amount' => 185000,
                'business_branch_id' => $mainBranch,
                'vendor' => 'Stationery World',
                'description' => 'Printer toner, paper, and pens',
                'payment_date' => now()->subDays(10),
                'status' => 'approved',
            ],
            [
                'expense_category_id' => $categories['Transport & Logistics'],
                'amount' => 320000,
                'business_branch_id' => $ntindaBranch,
                'vendor' => 'Fuel Station',
                'description' => 'Fuel for delivery van',
                'payment_date' => now()->subDays(3),
                'status' => 'approved',
            ],
            [
                'expense_category_id' => $categories['Maintenance & Repairs'],
                'amount' => 650000,
                'business_branch_id' => $mainBranch,
                'vendor' => 'Fix-It Services',
                'description' => 'AC repair and servicing',
                'payment_date' => now()->subDay(),
                'status' => 'pending',
            ],
            [
                'expense_category_id' => $categories['Marketing'],
                'amount' => 250000,
                'business_branch_id' => null,
                'vendor' => 'Social Media Ads',
                'description' => 'Facebook and Instagram ad campaign',
                'payment_date' => now(),
                'status' => 'pending',
            ],
        ];

        foreach ($expenses as $exp) {
            Expense::create([
                'expense_category_id' => $exp['expense_category_id'],
                'amount' => $exp['amount'],
                'business_id' => $business->id,
                'business_branch_id' => $exp['business_branch_id'],
                'vendor' => $exp['vendor'],
                'description' => $exp['description'],
                'payment_date' => $exp['payment_date'],
                'status' => $exp['status'],
            ]);
        }

        $this->command->info('✅ Seeded ' . count($expenses) . ' expenses');
    }
}
