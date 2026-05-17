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
        foreach ($branches as $branch) {
            foreach ($products as $product) {
                $names = ["Iphone 14 Pro Max", "Infinix Smart 7 HD", "MacBook Air PC", "Hard Disk Drive"];
                BusinessBranchProduct::create(
                    [
                        'business_branch_id' => $branch->id,
                        'product_id' => $product->id,
                        'quantity' => rand(2, 25),
                        'cost_price' => rand(50000, 180000) / 100,
                        'price' => rand(70000, 250000) / 100,
                        'name' => $names[array_rand($names)],
                        'reorder_level' => rand(2, 6),
                        'description' => $product->name . " available in branch stock",
                        'status' => "active",
                    ]
                );
            }
        }

        $this->command->info("✅ Realistic branch products seeded successfully!");
    }

}