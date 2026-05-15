<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessBranchProduct;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BusinessBranchProductSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");
        $products = Product::where("business_id",  $businessId)->get();
        $branches = BusinessBranch::where("business_id", $businessId)->get();
        // Fake real-world branch inventory products
        $branchProducts = [
            [
                'name' => 'MacBook Pro 14" M3',
                'sku' => 'MBP-14-M3',
            ],
            [
                'name' => 'MacBook Air M2',
                'sku' => 'MBA-M2-13',
            ],
            [
                'name' => 'Dell XPS 13 Plus',
                'sku' => 'DELL-XPS-13P',
            ],
            [
                'name' => 'HP Spectre x360',
                'sku' => 'HP-SPECTRE-360',
            ],
            [
                'name' => 'iPhone 15 Pro Max',
                'sku' => 'IPH-15PM',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'sku' => 'SGS24-U',
            ],
            [
                'name' => 'iPad Pro 12.9 M2',
                'sku' => 'IPAD-PRO-M2',
            ],
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'sku' => 'SONY-XM5',
            ],
            [
                'name' => 'LG 27" 4K Monitor',
                'sku' => 'LG-27-4K',
            ],
            [
                'name' => 'PlayStation 5 Console',
                'sku' => 'PS5-CONSOLE',
            ],
        ];

        foreach ($branches as $branch) {
            foreach ($products as $product) {
            foreach ($branchProducts as $item) {
                BusinessBranchProduct::create(
                    [
                        'business_branch_id' => $branch->id,
                        'product_id' => $product->id,
                        'quantity' => rand(2, 25),
                        'cost_price' => rand(50000, 180000) / 100,
                        'price' => rand(70000, 250000) / 100,
                        'reorder_level' => rand(2, 6),
                        'description' => $item['name'] . " available in branch stock",
                        'status' => "active",
                    ]
                );
            }
            }
        }

        $this->command->info("✅ Realistic branch products seeded successfully!");
    }

    private function fakeStatus(): string
    {
        return collect([
            'active',
            'inactive',
            'damaged',
            'out_of_stock',
        ])->random();
    }
}