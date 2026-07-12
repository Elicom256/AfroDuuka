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

        $categoryMap = [
            'Phones' => ['name' => 'iPhone 15 Pro Max', 'sku' => 'PH-1001', 'barcode' => '890100000001'],
            'Computers' => ['name' => 'MacBook Air M3', 'sku' => 'PC-1002', 'barcode' => '890100000002'],
            'Tablets & iPads' => ['name' => 'Samsung Galaxy Tab S9', 'sku' => 'TB-1003', 'barcode' => '890100000003'],
            'Audio & Headphones' => ['name' => 'Sony WH-1000XM5', 'sku' => 'AU-1004', 'barcode' => '890100000004'],
            'Cameras & Drones' => ['name' => 'Canon EOS R50', 'sku' => 'CM-1005', 'barcode' => '890100000005'],
            'TVs & Home Theater' => ['name' => 'Samsung 55" Smart TV', 'sku' => 'TV-1006', 'barcode' => '890100000006'],
            'Gaming' => ['name' => 'PlayStation 5', 'sku' => 'GM-1007', 'barcode' => '890100000007'],
            'Wearables' => ['name' => 'Apple Watch Series 10', 'sku' => 'WR-1008', 'barcode' => '890100000008'],
            'Home Appliances' => ['name' => 'Smart Refrigerator', 'sku' => 'HA-1009', 'barcode' => '890100000009'],
            'Networking' => ['name' => 'WiFi Router', 'sku' => 'NW-1010', 'barcode' => '890100000010'],
        ];

        foreach ($branches as $branch) {
            foreach ($categoryMap as $categoryName => $productData) {
                $category = $categories->firstWhere('name', strtolower($categoryName));
                Product::updateOrCreate(
                    ['business_branch_id' => $branch->id, 'name' => $productData['name']],
                    [
                        'product_category_id' => $category?->id,
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

        $this->command->info("✅ Products seeded successfully!");
    }
}
