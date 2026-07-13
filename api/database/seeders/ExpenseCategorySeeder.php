<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('email', 'testbusinessone@gmail.com')->first();

        $categories = [
            ['name' => 'Utilities', 'description' => 'Electricity, water, and internet bills'],
            ['name' => 'Office Supplies', 'description' => 'Stationery, printer ink, and office consumables'],
            ['name' => 'Transport & Logistics', 'description' => 'Fuel, delivery fees, and vehicle maintenance'],
            ['name' => 'Maintenance & Repairs', 'description' => 'Building and equipment repairs'],
            ['name' => 'Marketing', 'description' => 'Advertising, promotions, and branding'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::updateOrCreate(
                ['name' => $cat['name'], 'business_id' => $business->id],
                [
                    'description' => $cat['description'],
                    'business_id' => $business->id,
                ]
            );
        }

        $this->command->info('✅ Seeded ' . count($categories) . ' expense categories');
    }
}
