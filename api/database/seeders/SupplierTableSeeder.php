<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Prime Wholesale Ltd',
                'email' => 'primewholesale@example.com',
                'phone' => '+256700111222',
                'address' => 'Kampala Road, Kampala',
            ],
            [
                'name' => 'East Africa Supplies',
                'email' => 'easupplies@example.com',
                'phone' => '+256701333444',
                'address' => 'Ntinda, Kampala',
            ],
            [
                'name' => 'Global Traders Uganda',
                'email' => 'globaltraders@example.com',
                'phone' => '+256702555666',
                'address' => 'Nakasero, Kampala',
            ],
            [
                'name' => 'City Stock Providers',
                'email' => 'citystock@example.com',
                'phone' => '+256703777888',
                'address' => 'Wandegeya, Kampala',
            ],
            [
                'name' => 'Fresh Farm Distributors',
                'email' => 'freshfarm@example.com',
                'phone' => '+256704999000',
                'address' => 'Nakawa, Kampala',
            ],
        ];

        $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");
        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ["email" => $supplier['email']],
                 [...$supplier, "business_id" =>$businessId, "status" => "active"]);
        }
        $this->command->info("✅ Seeded " . count($suppliers) . " Suppliers Successfully!");
    }
}