<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleOrderRequest;
use App\Http\Requests\UpdateSaleOrderRequest;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = SaleOrder::with('items.product', 'customer.user')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['message' => 'Orders fetched', 'data' => $orders]);
    }

    public function store(StoreSaleOrderRequest $request): JsonResponse
    {
        // A sale order pins a unit_price and an allocated_qty for a future delivery,
        // so writing one is a pricing and commitment decision rather than a floor one.
        // canManageBranch() is the same expression a purchase order takes; the closer
        // analogue, QuotationPolicy::create, returns true for every role, which is
        // defensible for an unbound quote but not for an order that allocates stock.
        abort_unless(RolePermissions::canManageBranch($request->user()), 403, 'You cannot create sale orders.');

        $user = Auth::user();
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $user) {
            $orderCount = SaleOrder::where('business_id', $user->business_id)->count();
            $orderNumber = 'ORD-'.str_pad($orderCount + 1, 6, '0', STR_PAD_LEFT);

            $totalAmount = collect($validated['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $order = SaleOrder::create([
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                SaleOrderItem::create([
                    'sale_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'allocated_qty' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return response()->json(['message' => 'Order created', 'data' => $order->load('items.product', 'customer.user')], 201);
        });
    }

    public function show(SaleOrder $sale_order): JsonResponse
    {
        return response()->json(['message' => 'Order fetched', 'data' => $sale_order->load('items.product', 'customer.user')]);
    }

    public function update(UpdateSaleOrderRequest $request, SaleOrder $sale_order): JsonResponse
    {
        // The status this can move an order to includes 'approved' and 'delivered',
        // which is the sales-side twin of approving a purchase order — so the same
        // capability decides it. The DELETE beside it is already covered centrally by
        // the BlockRestrictedRoleActions allowlist.
        abort_unless(RolePermissions::canManageBranch($request->user()), 403, 'You cannot edit sale orders.');

        $sale_order->update($request->validated());

        return response()->json(['message' => 'Order updated', 'data' => $sale_order->load('items.product', 'customer.user')]);
    }

    public function destroy(SaleOrder $sale_order): JsonResponse
    {
        $sale_order->items()->delete();
        $sale_order->delete();

        return response()->json(['message' => 'Order deleted']);
    }
}
