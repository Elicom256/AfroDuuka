<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where("email", "testbusinessone@gmail.com")->first();
        if (!$business) {
            throw new \Exception("Business not found");
        }

        $branch = BusinessBranch::where("business_id", $business->id)
                   ->where("name", "Main Branch")
                   ->first();
        if (!$branch) {
            throw new \Exception("Branch not found");
        }

        $user = User::where("business_id", $business->id)->where("email", "admin@gmail.com")->first();
        $customers = Customer::whereHas("user", fn($q) => $q->where("business_id", $business->id))->get();
        $products = Product::where("business_branch_id", $branch->id)->get();

        if ($products->isEmpty()) {
            $this->command->warn("⚠️ No products found, skipping order seeding");
            return;
        }

        $orders = [
            [
                "status" => "delivered",
                "notes" => "Customer collected in person",
                "items" => [
                    ["product_index" => 0, "quantity" => 2],
                    ["product_index" => 1, "quantity" => 1],
                ],
            ],
            [
                "status" => "processing",
                "notes" => "Payment confirmed, preparing shipment",
                "items" => [
                    ["product_index" => 2, "quantity" => 1],
                ],
            ],
            [
                "status" => "pending",
                "notes" => null,
                "items" => [
                    ["product_index" => 0, "quantity" => 3],
                    ["product_index" => 3, "quantity" => 1],
                    ["product_index" => 4, "quantity" => 2],
                ],
            ],
            [
                "status" => "cancelled",
                "notes" => "Customer requested cancellation",
                "items" => [
                    ["product_index" => 1, "quantity" => 1],
                ],
            ],
            [
                "status" => "shipped",
                "notes" => "Dispatched via courier",
                "items" => [
                    ["product_index" => 5, "quantity" => 1],
                    ["product_index" => 6, "quantity" => 1],
                ],
            ],
        ];

        $orderCount = SaleOrder::where("business_id", $business->id)->count();

        foreach ($orders as $i => $orderData) {
            $orderCount++;
            $orderNumber = "ORD-" . str_pad($orderCount, 6, "0", STR_PAD_LEFT);
            $customer = $customers->isNotEmpty() ? $customers->random() : null;

            $totalAmount = 0;
            $items = [];
            foreach ($orderData["items"] as $itemData) {
                $product = $products[$itemData["product_index"] % $products->count()];
                $subtotal = $product->price * $itemData["quantity"];
                $totalAmount += $subtotal;
                $items[] = [
                    "product_id" => $product->id,
                    "quantity" => $itemData["quantity"],
                    "unit_price" => $product->price,
                    "subtotal" => $subtotal,
                ];
            }

            $order = SaleOrder::create([
                "business_id" => $business->id,
                "business_branch_id" => $branch->id,
                "user_id" => $user?->id,
                "customer_id" => $customer?->id,
                "order_number" => $orderNumber,
                "total_amount" => $totalAmount,
                "status" => $orderData["status"],
                "notes" => $orderData["notes"],
                "created_at" => now()->subDays(rand(1, 14)),
                "updated_at" => now()->subDays(rand(1, 14)),
            ]);

            foreach ($items as $item) {
                SaleOrderItem::create([
                    "order_id" => $order->id,
                    ...$item,
                ]);
            }
        }

        $this->command->info("✅ Orders seeded successfully!");
    }
}
