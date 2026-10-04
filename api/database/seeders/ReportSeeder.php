<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Report;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = Business::where('email', 'testbusinessone@gmail.com')->value('id');

        if (! $businessId) {
            return;
        }

        $reports = [
            [
                'name' => 'Daily Sales Summary',
                'type' => 'sales',
                'description' => 'Summary of all sales for the day including totals and top products',
                'schedule' => 'daily',
            ],
            [
                'name' => 'Weekly Inventory Report',
                'type' => 'inventory',
                'description' => 'Stock levels, low stock alerts, and reorder recommendations',
                'schedule' => 'weekly',
            ],
            [
                'name' => 'Monthly Financial Statement',
                'type' => 'financial',
                'description' => 'Revenue, expenses, profit/loss, and cash flow for the month',
                'schedule' => 'monthly',
            ],
            [
                'name' => 'Customer Purchase History',
                'type' => 'customers',
                'description' => 'Customer contact info and purchase history',
                'schedule' => null,
            ],
            [
                'name' => 'Supplier Orders',
                'type' => 'purchases',
                'description' => 'All purchase orders with supplier details and amounts',
                'schedule' => null,
            ],
        ];

        foreach ($reports as $report) {
            Report::updateOrCreate(
                ['business_id' => $businessId, 'name' => $report['name']],
                array_merge(['business_id' => $businessId], $report)
            );
        }
    }
}
