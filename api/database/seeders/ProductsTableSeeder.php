<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where("email", "testbusinessone@gmail.com")->first();
        $branches = BusinessBranch::where("business_id", $business->id)->get();
        $categories = ProductCategory::where("business_id", $business->id)->get();

        $products = [
            ['name' => 'Phones', 'sku' => 'PN-1001', 'barcode' => '890100000001'],
            ['name' => 'Iron Boxes', 'sku' => 'IB-1002', 'barcode' => '890100000002'],
            ['name' => 'Computers', 'sku' => 'PC-1003', 'barcode' => '890100000003'],
        ];

        foreach ($branches as $branch) {
            foreach ($products as $productData) {
                foreach ($categories as $category) {
                    Product::updateOrCreate(
                        ['business_branch_id' => $branch->id, 'name' => $productData['name']],
                        [
                            'product_category_id' => $category->id,
                            'sku' => $productData['sku'] . '-' . $branch->id,
                            'barcode' => $productData['barcode'],
                            'quantity' => rand(10, 50),
                            'cost_price' => rand(5000, 50000) / 100,
                            'price' => rand(8000, 80000) / 100,
                            'status' => 'active',
                        ]
                    );
                }
            }
        }

        $this->command->info("✅ Products seeded successfully!");
    }
}
