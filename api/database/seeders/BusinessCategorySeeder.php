<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

class BusinessCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                "name" => "Retail",
                "description" => "Businesses selling goods directly to consumers",
            ],
            [
                "name" => "Wholesale",
                "description" => "Bulk suppliers and distributors",
            ],
            [
                "name" => "Restaurant",
                "description" => "Food and beverage businesses",
            ],
            [
                "name" => "Pharmacy",
                "description" => "Medical and pharmaceutical businesses",
            ],
            [
                "name" => "Electronics",
                "description" => "Businesses selling electronics and gadgets",
            ],
            [
                "name" => "Supermarket",
                "description" => "Large-scale retail food and household goods",
            ],
            [
                "name" => "Other",
                "description" => "Any business",
            ],
        ];

        foreach ($categories as $category) {
            BusinessCategory::updateOrCreate(
                [
                    "name" => strtolower($category["name"]),
                ],
                [
                    "description" => strtolower($category["description"]),
                    "status" => 1,
                ]
            );
            $this->command->info("✅ Seeded " . $category['name'] . " Successfully!");
        }
    }
}