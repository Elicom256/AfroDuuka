<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Get business safely
        $business = Business::where("email", "testbusinessone@gmail.com")->first();
        $categories = Category::where("business_id", $business->id)->get();
        if (!$business) {
            throw new \Exception("Business not found");
        }
        $products = [
    [
        'name' => 'Wireless Mouse',
        'sku' => 'WM-1001',
        'barcode' => '890100000001',
        'category_id' => 1,
        'price' => 25.99,
        'cost_price' => 18.50,
        'quantity' => 50,
        'reorder_level' => 10,
        'status' => 'active',
        'description' => 'Ergonomic wireless mouse',
    ],
    [
        'name' => 'Mechanical Keyboard',
        'sku' => 'MK-1002',
        'barcode' => '890100000002',
        'category_id' => 1,
        'price' => 79.99,
        'cost_price' => 60.00,
        'quantity' => 30,
        'reorder_level' => 5,
        'status' => 'active',
        'description' => 'RGB mechanical keyboard',
    ],
    [
        'name' => 'USB-C Charger',
        'sku' => 'UC-1003',
        'barcode' => '890100000003',
        'category_id' => 2,
        'price' => 19.99,
        'cost_price' => 12.00,
        'quantity' => 100,
        'reorder_level' => 20,
        'status' => 'active',
        'description' => 'Fast charging USB-C adapter',
    ],
    [
        'name' => 'Laptop Stand',
        'sku' => 'LS-1004',
        'barcode' => '890100000004',
        'category_id' => 2,
        'price' => 35.00,
        'cost_price' => 22.00,
        'quantity' => 40,
        'reorder_level' => 8,
        'status' => 'active',
        'description' => 'Adjustable aluminum laptop stand',
    ],
    [
        'name' => 'Bluetooth Speaker',
        'sku' => 'BS-1005',
        'barcode' => '890100000005',
        'category_id' => 3,
        'price' => 59.99,
        'cost_price' => 42.00,
        'quantity' => 25,
        'reorder_level' => 5,
        'status' => 'active',
        'description' => 'Portable Bluetooth speaker',
    ],
    [
        'name' => 'Gaming Headset',
        'sku' => 'GH-1006',
        'barcode' => '890100000006',
        'category_id' => 3,
        'price' => 89.99,
        'cost_price' => 65.00,
        'quantity' => 20,
        'reorder_level' => 5,
        'status' => 'active',
        'description' => 'Surround sound gaming headset',
    ],
    [
        'name' => 'Webcam HD',
        'sku' => 'WC-1007',
        'barcode' => '890100000007',
        'category_id' => 4,
        'price' => 49.99,
        'cost_price' => 32.00,
        'quantity' => 35,
        'reorder_level' => 7,
        'status' => 'active',
        'description' => '1080p HD webcam',
    ],
    [
        'name' => 'External Hard Drive 1TB',
        'sku' => 'HD-1008',
        'barcode' => '890100000008',
        'category_id' => 4,
        'price' => 99.99,
        'cost_price' => 75.00,
        'quantity' => 15,
        'reorder_level' => 3,
        'status' => 'active',
        'description' => 'Portable 1TB external hard drive',
    ],
    [
        'name' => 'Power Bank 20000mAh',
        'sku' => 'PB-1009',
        'barcode' => '890100000009',
        'category_id' => 5,
        'price' => 45.00,
        'cost_price' => 30.00,
        'quantity' => 60,
        'reorder_level' => 10,
        'status' => 'active',
        'description' => 'High capacity power bank',
    ],
    [
        'name' => 'Smart Watch',
        'sku' => 'SW-1010',
        'barcode' => '890100000010',
        'category_id' => 5,
        'price' => 149.99,
        'cost_price' => 110.00,
        'quantity' => 18,
        'reorder_level' => 4,
        'status' => 'active',
        'description' => 'Fitness tracking smart watch',
    ],
    [
        'name' => 'LED Desk Lamp',
        'sku' => 'DL-1011',
        'barcode' => '890100000011',
        'category_id' => 6,
        'price' => 29.99,
        'cost_price' => 18.00,
        'quantity' => 45,
        'reorder_level' => 8,
        'status' => 'active',
        'description' => 'Dimmable LED desk lamp',
    ],
    [
        'name' => 'Office Chair',
        'sku' => 'OC-1012',
        'barcode' => '890100000012',
        'category_id' => 6,
        'price' => 199.99,
        'cost_price' => 150.00,
        'quantity' => 10,
        'reorder_level' => 2,
        'status' => 'active',
        'description' => 'Comfortable ergonomic office chair',
    ],
    [
        'name' => 'Wireless Earbuds',
        'sku' => 'WE-1013',
        'barcode' => '890100000013',
        'category_id' => 7,
        'price' => 69.99,
        'cost_price' => 50.00,
        'quantity' => 40,
        'reorder_level' => 8,
        'status' => 'active',
        'description' => 'Noise cancelling earbuds',
    ],
    [
        'name' => 'Phone Tripod',
        'sku' => 'PT-1014',
        'barcode' => '890100000014',
        'category_id' => 7,
        'price' => 22.99,
        'cost_price' => 14.00,
        'quantity' => 55,
        'reorder_level' => 10,
        'status' => 'active',
        'description' => 'Adjustable smartphone tripod',
    ],
    [
        'name' => 'Graphic Tablet',
        'sku' => 'GT-1015',
        'barcode' => '890100000015',
        'category_id' => 8,
        'price' => 120.00,
        'cost_price' => 90.00,
        'quantity' => 12,
        'reorder_level' => 3,
        'status' => 'active',
        'description' => 'Digital drawing tablet',
    ],
    [
        'name' => 'Portable Monitor',
        'sku' => 'PM-1016',
        'barcode' => '890100000016',
        'category_id' => 8,
        'price' => 180.00,
        'cost_price' => 140.00,
        'quantity' => 14,
        'reorder_level' => 3,
        'status' => 'active',
        'description' => '15-inch portable monitor',
    ],
    [
        'name' => 'Ethernet Cable 5m',
        'sku' => 'EC-1017',
        'barcode' => '890100000017',
        'category_id' => 9,
        'price' => 12.50,
        'cost_price' => 5.00,
        'quantity' => 120,
        'reorder_level' => 20,
        'status' => 'active',
        'description' => 'High-speed CAT6 cable',
    ],
    [
        'name' => 'WiFi Router',
        'sku' => 'WR-1018',
        'barcode' => '890100000018',
        'category_id' => 9,
        'price' => 89.99,
        'cost_price' => 65.00,
        'quantity' => 22,
        'reorder_level' => 5,
        'status' => 'active',
        'description' => 'Dual-band WiFi router',
    ],
    [
        'name' => 'Action Camera',
        'sku' => 'AC-1019',
        'barcode' => '890100000019',
        'category_id' => 10,
        'price' => 210.00,
        'cost_price' => 170.00,
        'quantity' => 9,
        'reorder_level' => 2,
        'status' => 'active',
        'description' => '4K waterproof action camera',
    ],
    [
        'name' => 'Mini Projector',
        'sku' => 'MP-1020',
        'barcode' => '890100000020',
        'category_id' => 10,
        'price' => 250.00,
        'cost_price' => 200.00,
        'quantity' => 7,
        'reorder_level' => 2,
        'status' => 'active',
        'description' => 'Compact HD mini projector',
    ],
];

foreach ($categories as $category) {
    foreach ($products as $product) {
        Product::updateOrCreate([  "sku" => $product["sku"] ],
            array_merge($product, ["category_id" => $category->id, 'business_id' => $business->id])
           );
    }
}
$this->command->info("✅ Seeded " . count($products) . " Products Successfully!");
    }
}
