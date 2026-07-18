<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $orders = Order::where("business_branch_id", $user->business_branch_id)
            ->with("items.product", "customer")
            ->orderByDesc("created_at")
            ->get();

        return response()->json(["message" => "Orders fetched", "data" => $orders]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            "customer_id" => "nullable|exists:customers,id",
            "items" => "required|array|min:1",
            "items.*.product_id" => "required|exists:products,id",
            "items.*.quantity" => "required|integer|min:1",
            "items.*.unit_price" => "required|numeric|min:0",
            "notes" => "nullable|string|max:500",
        ]);

        return DB::transaction(function () use ($validated, $user) {
            $orderCount = Order::where("business_id", $user->business_id)->count();
            $orderNumber = "ORD-" . str_pad($orderCount + 1, 6, "0", STR_PAD_LEFT);

            $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["unit_price"]);

            $order = Order::create([
                "business_id" => $user->business_id,
                "business_branch_id" => $user->business_branch_id,
                "user_id" => $user->id,
                "customer_id" => $validated["customer_id"] ?? null,
                "order_number" => $orderNumber,
                "total_amount" => $totalAmount,
                "status" => "pending",
                "notes" => $validated["notes"] ?? null,
            ]);

            foreach ($validated["items"] as $item) {
                OrderItem::create([
                    "order_id" => $order->id,
                    "product_id" => $item["product_id"],
                    "quantity" => $item["quantity"],
                    "unit_price" => $item["unit_price"],
                    "subtotal" => $item["quantity"] * $item["unit_price"],
                ]);
            }

            return response()->json(["message" => "Order created", "data" => $order->load("items.product", "customer")], 201);
        });
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json(["message" => "Order fetched", "data" => $order->load("items.product", "customer")]);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            "status" => "sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled",
            "notes" => "nullable|string|max:500",
        ]);

        $order->update($validated);

        return response()->json(["message" => "Order updated", "data" => $order->load("items.product", "customer")]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $order->items()->delete();
        $order->delete();
        return response()->json(["message" => "Order deleted"]);
    }
}
