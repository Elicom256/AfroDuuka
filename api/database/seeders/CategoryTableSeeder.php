<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Category;
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
                "name" => "Electronics",
                "description" => "Phones, laptops, gadgets and accessories",
            ],
            [
                "name" => "Fashion",
                "description" => "Clothing, shoes and fashion items",
            ],
            [
                "name" => "Groceries",
                "description" => "Food and household essentials",
            ],
            [
                "name" => "Home & Living",
                "description" => "Furniture, decor and home appliances",
            ],
            [
                "name" => "Health & Beauty",
                "description" => "Cosmetics and personal care products",
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
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