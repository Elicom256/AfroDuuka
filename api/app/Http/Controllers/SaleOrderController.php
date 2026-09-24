<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleOrderRequest;
use App\Http\Requests\UpdateSaleOrderRequest;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = SaleOrder::with("items.product", "customer")
            ->orderByDesc("created_at")
            ->get();

        return response()->json(["message" => "Orders fetched", "data" => $orders]);
    }

    public function store(StoreSaleOrderRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $user) {
            $orderCount = SaleOrder::where("business_id", $user->business_id)->count();
            $orderNumber = "ORD-" . str_pad($orderCount + 1, 6, "0", STR_PAD_LEFT);

            $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["unit_price"]);

            $order = SaleOrder::create([
                "user_id" => $user->id,
                "customer_id" => $validated["customer_id"] ?? null,
                "order_number" => $orderNumber,
                "total_amount" => $totalAmount,
                "status" => "pending",
                "notes" => $validated["notes"] ?? null,
            ]);

            foreach ($validated["items"] as $item) {
                SaleOrderItem::create([
                    "sale_order_id" => $order->id,
                    "product_id" => $item["product_id"],
                    "quantity" => $item["quantity"],
                    "allocated_qty" => $item["quantity"],
                    "unit_price" => $item["unit_price"],
                    "subtotal" => $item["quantity"] * $item["unit_price"],
                ]);
            }

            return response()->json(["message" => "Order created", "data" => $order->load("items.product", "customer")], 201);
        });
    }

    public function show(SaleOrder $sale_order): JsonResponse
    {
        return response()->json(["message" => "Order fetched", "data" => $sale_order->load("items.product", "customer")]);
    }

    public function update(UpdateSaleOrderRequest $request, SaleOrder $sale_order): JsonResponse
    {
        $sale_order->update($request->validated());

        return response()->json(["message" => "Order updated", "data" => $sale_order->load("items.product", "customer")]);
    }

    public function destroy(SaleOrder $sale_order): JsonResponse
    {
        $sale_order->items()->delete();
        $sale_order->delete();
        return response()->json(["message" => "Order deleted"]);
    }
}
