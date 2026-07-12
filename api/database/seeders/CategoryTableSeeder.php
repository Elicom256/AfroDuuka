<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = Business::where("email", "testbusinessone@gmail.com")->first();

        if (!$business) {
            throw new \Exception("Business not found");
        }

        $categories = [
            [
                "name" => "Phones",
                "description" => "Mobile phones and smartphones",
            ],
            [
                "name" => "Computers",
                "description" => "Laptops, desktops and computer accessories",
            ],
            [
                "name" => "Tablets & iPads",
                "description" => "Tablets, iPads and e-readers",
            ],
            [
                "name" => "Audio & Headphones",
                "description" => "Headphones, speakers and audio equipment",
            ],
            [
                "name" => "Cameras & Drones",
                "description" => "Cameras, drones and photography gear",
            ],
            [
                "name" => "TVs & Home Theater",
                "description" => "Televisions and home entertainment systems",
            ],
            [
                "name" => "Gaming",
                "description" => "Gaming consoles and accessories",
            ],
            [
                "name" => "Wearables",
                "description" => "Smartwatches and fitness trackers",
            ],
            [
                "name" => "Home Appliances",
                "description" => "Refrigerators, washing machines and kitchen appliances",
            ],
            [
                "name" => "Networking",
                "description" => "Routers, modems and networking equipment",
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::updateOrCreate(
                [
                    "name" => strtolower($category["name"]),
                    "business_id" => $business->id,
                ],
                [
                    "description" => strtolower($category["description"]),
                    "status" => "active",
                ]
            );
              $this->command->info("✅ Seeded " . $category['name'] . " Successfully!");
        }
    }
}