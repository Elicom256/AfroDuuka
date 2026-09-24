<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\Concerns\SeedsFixtureBusiness;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    use SeedsFixtureBusiness;

    public function run(): void
    {
        $business = $this->fixtureBusiness();

        $branch = BusinessBranch::where("business_id", $business->id)
                   ->where("name", "Main Branch")
                   ->first()
                   ?? $this->fixtureMainBranch($business);

        $user = User::where("business_id", $business->id)->where("email", "admin@gmail.com")->first();
        $customers = Customer::whereHas("user", fn($q) => $q->where("business_id", $business->id))->get();
        $products = Product::where("business_branch_id", $branch->id)->get();

        if ($products->isEmpty()) {
            $this->command->warn("⚠️ No products found, skipping order seeding");
            return;
        }

        $orders = [
            [
                "status" => "approved",
                "notes" => "Customer collected in person",
                "items" => [
                    ["product_index" => 0, "quantity" => 2],
                    ["product_index" => 1, "quantity" => 1],
                ],
            ],
            [
                "status" => "pending",
                "notes" => "Awaiting approval",
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
                "status" => "approved",
                "notes" => "Approved, awaiting dispatch",
                "items" => [
                    ["product_index" => 5, "quantity" => 1],
                    ["product_index" => 6, "quantity" => 1],
                ],
            ],
        ];

        $orderCount = SaleOrder::where("business_id", $business->id)->count();

        if ($orderCount > 0) {
            $this->command->info("✅ Orders already seeded, skipping.");
            return;
        }

        foreach ($orders as $i => $orderData) {
            $orderCount++;
            $orderNumber = "ORD-" . str_pad($orderCount, 6, "0", STR_PAD_LEFT);
            $customer = $customers->isNotEmpty() ? $customers->random() : null;

            $totalAmount = 0;
            $items = [];
            foreach ($orderData["items"] as $itemData) {
                $product = $products[$itemData["product_index"] % $products->count()];
                $subtotal = $product->selling_price * $itemData["quantity"];
                $totalAmount += $subtotal;
                $items[] = [
                    "product_id" => $product->id,
                    "quantity" => $itemData["quantity"],
                    "unit_price" => $product->selling_price,
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
                    "sale_order_id" => $order->id,
                    ...$item,
                ]);
            }
        }

        $this->command->info("✅ Orders seeded successfully!");
    }
}
