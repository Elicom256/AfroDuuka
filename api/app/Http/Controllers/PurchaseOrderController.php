<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $orders = PurchaseOrder::where("business_branch_id", $user->business_branch_id)
            ->with("items.product", "supplier")
            ->orderByDesc("created_at")
            ->get();

        return response()->json(["message" => "Purchase orders fetched", "data" => $orders]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            "supplier_id" => "required|exists:suppliers,id",
            "items" => "required|array|min:1",
            "items.*.product_id" => "required|exists:products,id",
            "items.*.quantity" => "required|integer|min:1",
            "items.*.unit_price" => "required|numeric|min:0",
            "notes" => "nullable|string|max:500",
        ]);

        return DB::transaction(function () use ($validated, $user) {
            $orderCount = PurchaseOrder::where("business_id", $user->business_id)->count();
            $orderNumber = "PO-" . str_pad($orderCount + 1, 6, "0", STR_PAD_LEFT);

            $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["unit_price"]);

            $order = PurchaseOrder::create([
                "business_id" => $user->business_id,
                "business_branch_id" => $user->business_branch_id,
                "user_id" => $user->id,
                "supplier_id" => $validated["supplier_id"],
                "order_number" => $orderNumber,
                "total_amount" => $totalAmount,
                "status" => "pending",
                "notes" => $validated["notes"] ?? null,
            ]);

            foreach ($validated["items"] as $item) {
                PurchaseOrderItem::create([
                    "purchase_order_id" => $order->id,
                    "product_id" => $item["product_id"],
                    "quantity" => $item["quantity"],
                    "unit_price" => $item["unit_price"],
                    "subtotal" => $item["quantity"] * $item["unit_price"],
                ]);
            }

            return response()->json(["message" => "Purchase order created", "data" => $order->load("items.product", "supplier")], 201);
        });
    }

    public function show(PurchaseOrder $purchase_order): JsonResponse
    {
        return response()->json(["message" => "Purchase order fetched", "data" => $purchase_order->load("items.product", "supplier")]);
    }

    public function update(Request $request, PurchaseOrder $purchase_order): JsonResponse
    {
        $validated = $request->validate([
            "status" => "sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled",
            "notes" => "nullable|string|max:500",
        ]);

        $purchase_order->update($validated);

        return response()->json(["message" => "Purchase order updated", "data" => $purchase_order->load("items.product", "supplier")]);
    }

    public function destroy(PurchaseOrder $purchase_order): JsonResponse
    {
        $purchase_order->items()->delete();
        $purchase_order->delete();
        return response()->json(["message" => "Purchase order deleted"]);
    }
}
