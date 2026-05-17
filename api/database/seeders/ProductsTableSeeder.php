<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
{
    $business = Business::where("email", "testbusinessone@gmail.com")->first();

    $products = [
        [
            'name' => 'Mouse',
            'sku' => 'WM-1001',
            'barcode' => '890100000001',
            'status' => 'active',
        ],
        [
            'name' => 'Mechanical Keyboard',
            'sku' => 'MK-1002',
            'barcode' => '890100000002',
            'status' => 'active',
        ],
        [
            'name' => 'USB',
            'sku' => 'UC-1003',
            'barcode' => '890100000003',
            'status' => 'active',
        ],
        // ... keep rest same but only catalog fields
    ];

    foreach ($products as  $productData) {
        $categories = Category::where("business_id", $business->id)->get();
        foreach ($categories as $category) {
            Product::updateOrCreate([
                'business_id' => $business->id,
                'sku' => $productData['sku'],
            ],
            [
                'name' => $productData['name'],
                'barcode' => $productData['barcode'],
                'category_id' => $category->id,
                'status' => $productData['status'],
            ]);
        }
    }

    $this->command->info("✅ Products and inventory seeded successfully!");
}
}
