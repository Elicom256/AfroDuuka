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
                $names = [
                "Iphone 14 Pro Max", "Infinix Smart 7 HD", "MacBook Air PC", "Hard Disk Drive",
                "Samsung Galaxy S24 Ultra", "Google Pixel 9", "Tecno Camon 30", "Xiaomi Redmi Note 13",
                 "OnePlus 12", "Huawei P60 Pro", "Nokia G22", "Oppo Reno 11", "Vivo V30",
                 "iPad Pro", "Samsung Galaxy Tab S9", "Lenovo Tab M11", "Dell XPS 13",
                 "HP Pavilion Laptop", "Lenovo ThinkPad X1", "Asus ROG Zephyrus", "Acer Aspire 5",
                 "MSI Gaming Laptop", "Samsung 55 Inch Smart TV", "LG OLED TV", "Sony Bravia TV",
                 "Hisense Smart TV", "JBL Bluetooth Speaker", "Sony WH-1000XM5 Headphones", "AirPods Pro",
                 "Samsung Galaxy Buds 3", "Apple Watch Series 10", "Samsung Galaxy Watch 7", "Fitbit Charge 6",
                 "Canon EOS R50 Camera", "Nikon Z50 Camera", "GoPro Hero 13", "PlayStation 5",
                 "Xbox Series X", "Nintendo Switch OLED", "External SSD 1TB", "USB Flash Drive 128GB",
                 "Wireless Mouse", "Mechanical Keyboard", "WiFi Router", "Laser Printer",
                 "Smart Refrigerator", "Washing Machine", "Microwave Oven", "Air Conditioner","Vacuum Cleaner"
             ];
                $costPrice = rand(50000, 180000) / 100;
                $markup_percentage = rand(15, 50) / 100;
                $price = $costPrice * (1 + $markup_percentage);
                foreach($names as $name){
                BusinessBranchProduct::updateOrCreate(
                    ['business_branch_id' => $branch->id, 'name' => $name],
                    [
                        'product_id' => $product->id,
                        'quantity' => rand(2, 25),
                        'cost_price' => $costPrice,
                        'price' => $price,
                        "markup_percentage" => $markup_percentage,
                        'reorder_level' => rand(2, 6),
                        'description' => $product->name . " available in branch stock",
                        'status' => "active",
                    ]
                );
            }
            }
        }

        $this->command->info("✅ Realistic branch products seeded successfully!");
    }

}