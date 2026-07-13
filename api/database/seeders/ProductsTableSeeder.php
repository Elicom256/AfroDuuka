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

        if (!$business) {
            throw new \Exception("Business not found");
        }

        $branches = BusinessBranch::where('business_id', $business->id)->get();

        $categoryMap = [
            'Phones' => ['name' => 'iPhone 15 Pro Max', 'sku' => 'PH-1001', 'barcode' => '890100000001', 'description' => 'Mobile phones and smartphones'],
            'Computers' => ['name' => 'MacBook Air M3', 'sku' => 'PC-1002', 'barcode' => '890100000002', 'description' => 'Laptops, desktops and computer accessories'],
            'Tablets & iPads' => ['name' => 'Samsung Galaxy Tab S9', 'sku' => 'TB-1003', 'barcode' => '890100000003', 'description' => 'Tablets, iPads and e-readers'],
            'Audio & Headphones' => ['name' => 'Sony WH-1000XM5', 'sku' => 'AU-1004', 'barcode' => '890100000004', 'description' => 'Headphones, speakers and audio equipment'],
            'Cameras & Drones' => ['name' => 'Canon EOS R50', 'sku' => 'CM-1005', 'barcode' => '890100000005', 'description' => 'Cameras, drones and photography gear'],
            'TVs & Home Theater' => ['name' => 'Samsung 55" Smart TV', 'sku' => 'TV-1006', 'barcode' => '890100000006', 'description' => 'Televisions and home entertainment systems'],
            'Gaming' => ['name' => 'PlayStation 5', 'sku' => 'GM-1007', 'barcode' => '890100000007', 'description' => 'Gaming consoles and accessories'],
            'Wearables' => ['name' => 'Apple Watch Series 10', 'sku' => 'WR-1008', 'barcode' => '890100000008', 'description' => 'Smartwatches and fitness trackers'],
            'Home Appliances' => ['name' => 'Smart Refrigerator', 'sku' => 'HA-1009', 'barcode' => '890100000009', 'description' => 'Refrigerators, washing machines and kitchen appliances'],
            'Networking' => ['name' => 'WiFi Router', 'sku' => 'NW-1010', 'barcode' => '890100000010', 'description' => 'Routers, modems and networking equipment'],
        ];

        foreach ($categoryMap as $name => $data) {
            ProductCategory::updateOrCreate(
                [
                    'name' => strtolower($name),
                    'business_id' => $business->id,
                ],
                [
                    'description' => strtolower($data['description']),
                    'status' => 'active',
                ]
            );
        }

        $productCategories = ProductCategory::where('business_id', $business->id)->get();

        foreach ($branches as $branch) {
            foreach ($categoryMap as $categoryName => $productData) {
                $category = $productCategories->firstWhere('name', strtolower($categoryName));
                $amount = rand(50000, 5000000);
                Product::updateOrCreate(
                    ['business_branch_id' => $branch->id, 'name' => $productData['name']],
                    [
                        'product_category_id' => $category?->id,
                        'sku' => $productData['sku'] . '-' . $branch->id,
                        'barcode' => $productData['barcode'],
                        'quantity' => rand(10, 50),
                        'markup_percentage' => random_int(20,90) / 100,
                        'cost_price' => $amount,
                        'price' => $amount / 2,
                        'reorder_level' => rand(5, 15),
                        'description' => 'some description for the product here',
                        'status' => 'active',
                    ]
                );
            }
        }

        $this->command->info("✅ Product categories and products seeded successfully!");
    }
}
