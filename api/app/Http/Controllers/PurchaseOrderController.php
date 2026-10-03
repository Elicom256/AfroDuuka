<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = PurchaseOrder::with("items.product", "supplier")
            ->orderByDesc("created_at")
            ->get();

        return response()->json(["message" => "Purchase orders fetched", "data" => $orders]);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        abort_unless(RolePermissions::canCreatePurchaseOrder(Auth::user()), 403);

        $user = Auth::user();
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $user) {
            $orderCount = PurchaseOrder::where("business_id", $user->business_id)->count();
            $orderNumber = "PO-" . str_pad($orderCount + 1, 6, "0", STR_PAD_LEFT);

            $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["unit_price"]);

            $order = PurchaseOrder::create([
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

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchase_order): JsonResponse
    {
        $validated = $request->validated();

        abort_unless(RolePermissions::canCreatePurchaseOrder($request->user()), 403);

        if (($validated['status'] ?? null) === 'approved') {
            abort_unless(RolePermissions::canApprovePurchaseOrder($request->user()), 403);
        }

        $purchase_order->update($validated);

        return response()->json(["message" => "Purchase order updated", "data" => $purchase_order->load("items.product", "supplier")]);
    }

    public function destroy(PurchaseOrder $purchase_order): JsonResponse
    {
        abort_unless(RolePermissions::canApprovePurchaseOrder(Auth::user()), 403);

        $purchase_order->items()->delete();
        $purchase_order->delete();
        return response()->json(["message" => "Purchase order deleted"]);
    }
}
