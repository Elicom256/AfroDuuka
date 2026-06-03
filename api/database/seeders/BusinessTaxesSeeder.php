<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessTaxes;
use Illuminate\Database\Seeder;

class BusinessTaxesSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");

        $branchId = BusinessBranch::where("business_id", $businessId)
            ->where("name", "Main Branch")
            ->value("id");

        $taxes = [
            [
                'name' => 'VAT',
                'rate' => 18.00,
                'type' => 'percentage',
                'description' => 'Value Added Tax',
                'status' => 'active',
            ],
            [
                'name' => 'Service Tax',
                'rate' => 5.00,
                'type' => 'percentage',
                'description' => 'Service charge tax',
                'status' => 'active',
            ],
            [
                'name' => 'Withholding Tax',
                'rate' => 10.00,
                'type' => 'percentage',
                'description' => 'Withholding tax on transactions',
                'status' => 'active',
            ],
            [
                'name' => 'Fixed Levy',
                'rate' => 5000.00,
                'type' => 'fixed',
                'description' => 'Fixed government levy',
                'status' => 'active',
            ],
        ];

        foreach ($taxes as $tax) {
            BusinessTaxes::updateOrCreate(
                ['business_id' => $businessId, 'name' => $tax['name'],],
                [
                'rate' => $tax['rate'],
                'type' => $tax['type'],
                'description' => $tax['description'],
                'status' => $tax['status'],
            ]);
        }
        $num = count(collect($taxes));
        if($num){
            $this->command->info("✅ Seeded $num Business  Taxes!");
        }
            
    }
}