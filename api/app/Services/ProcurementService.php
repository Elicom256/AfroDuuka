<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Support\Tenant\EffectiveBranchScope;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function reorderSuggestions(?string $branchId = null): array
    {
        $user = Auth::user();
        $resolved = $user ? EffectiveBranchScope::branchesFor($user) : null;

        $query = Product::query()
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->where('status', 'active');

        if ($branchId) {
            if ($resolved !== null) {
                [, $branchIds] = $resolved;
                if (! in_array((int) $branchId, $branchIds, true)) {
                    throw new Exception('Branch is not within your allowed scope', 403);
                }
            }
            $query->where('business_branch_id', $branchId);
        } elseif ($resolved !== null) {
            [, $branchIds] = $resolved;
            $query->whereIn('business_branch_id', $branchIds);
        }

        $products = $query->get();

        $suggestions = $products->map(function (Product $product) {
            $avgDailySales = $this->calculateSalesVelocity($product);
            $daysRemaining = $avgDailySales > 0
                ? round($product->quantity / $avgDailySales, 1)
                : 999;

            $suggestedQty = $this->calculateSuggestedOrderQuantity($product, $avgDailySales);

            $supplier = $this->resolveSupplier($product);

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => (int) $product->quantity,
                'reorder_level' => (int) $product->reorder_level,
                'avg_daily_sales' => round($avgDailySales, 2),
                'days_remaining' => $daysRemaining,
                'suggested_order_quantity' => $suggestedQty,
                'last_purchase_price' => $product->cost_price,
                'estimated_order_value' => round($suggestedQty * (float) $product->cost_price, 2),
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplier?->company_name,
                'branch_id' => $product->business_branch_id,
            ];
        });

        return [
            'suggestions' => $suggestions,
            'total_suggestions' => $suggestions->count(),
            'total_estimated_value' => round($suggestions->sum('estimated_order_value'), 2),
        ];
    }

    public function calculateSalesVelocity(Product $product, int $days = 30): float
    {
        $totalSold = SaleItem::where('product_id', $product->id)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->sum('quantity');

        return round($totalSold / $days, 2);
    }

    public function calculateStockCoverage(Product $product, int $days = 30): float
    {
        $avgDailySales = $this->calculateSalesVelocity($product, $days);

        if ($avgDailySales <= 0) {
            return 999;
        }

        return round($product->quantity / $avgDailySales, 1);
    }

    public function calculateSuggestedOrderQuantity(Product $product, ?float $avgDailySales = null): int
    {
        $avgDailySales ??= $this->calculateSalesVelocity($product);

        if ($avgDailySales <= 0) {
            return (int) max($product->reorder_level * 2, 10);
        }

        $leadTimeDays = 7;
        $targetDays = 30;
        $targetStock = (int) ceil($avgDailySales * $targetDays);
        $suggested = max($targetStock - (int) $product->quantity, (int) $product->reorder_level);

        return max($suggested, 1);
    }

    public function createPurchaseOrder(array $data, ?string $branchId = null): PurchaseOrder
    {
        $user = Auth::user();
        $resolved = $user ? EffectiveBranchScope::branchesFor($user) : null;

        $branchId = $data['business_branch_id'] ?? $branchId ?? $user?->business_branch_id;

        if ($branchId && $resolved !== null) {
            [, $branchIds] = $resolved;
            if (! in_array((int) $branchId, $branchIds, true)) {
                throw new Exception('Branch is not within your allowed scope', 403);
            }
        }

        return DB::transaction(function () use ($data, $branchId, $user) {
            $orderCount = PurchaseOrder::where('business_id', $user->business_id)->count();
            $orderNumber = 'PO-'.str_pad($orderCount + 1, 6, '0', STR_PAD_LEFT);

            $totalAmount = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $order = PurchaseOrder::create([
                'business_id' => $user->business_id,
                'business_branch_id' => $branchId,
                'user_id' => $user->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => $data['status'] ?? 'draft',
                'order_date' => $data['order_date'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order->load('items.product', 'supplier');
        });
    }

    public function approvePurchaseOrder(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== 'pending' && $order->status !== 'draft') {
            throw new Exception('Only draft or pending purchase orders can be approved.', 422);
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return $order->fresh()->load('items.product', 'supplier');
    }

    public function markAsOrdered(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== 'approved') {
            throw new Exception('Only approved purchase orders can be marked as ordered.', 422);
        }

        $order->update([
            'status' => 'ordered',
            'order_date' => $order->order_date ?? now()->toDateString(),
        ]);

        return $order->fresh()->load('items.product', 'supplier');
    }

    public function receivePurchaseOrder(PurchaseOrder $order, array $receivedItems): PurchaseOrder
    {
        if (! in_array($order->status, ['ordered', 'partially_received'], true)) {
            throw new Exception('Only ordered or partially received purchase orders can be received.', 422);
        }

        return DB::transaction(function () use ($order, $receivedItems) {
            foreach ($order->items as $item) {
                $receivedQty = (int) ($receivedItems[$item->id] ?? 0);

                if ($receivedQty < 0) {
                    throw new Exception('Received quantity cannot be negative.', 422);
                }

                $newReceived = (int) $item->received_quantity + $receivedQty;

                if ($newReceived > (int) $item->quantity) {
                    throw new Exception("Received quantity cannot exceed ordered quantity for item {$item->product_id}.", 422);
                }

                $item->update(['received_quantity' => $newReceived]);

                if ($receivedQty > 0) {
                    $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $this->inventoryService->stockIn(
                            $product,
                            $receivedQty,
                            'purchase_order',
                            $order->id
                        );
                    }
                }
            }

            $allReceived = $order->items->every(fn ($item) => (int) $item->received_quantity >= (int) $item->quantity);
            $anyReceived = $order->items->contains(fn ($item) => (int) $item->received_quantity > 0);

            $newStatus = $allReceived ? 'received' : ($anyReceived ? 'partially_received' : $order->status);

            $order->update([
                'status' => $newStatus,
                'received_at' => $allReceived ? now() : $order->received_at,
                'received_by' => $allReceived ? Auth::id() : $order->received_by,
            ]);

            return $order->fresh()->load('items.product', 'supplier');
        });
    }

    public function cancelPurchaseOrder(PurchaseOrder $order): PurchaseOrder
    {
        if (in_array($order->status, ['received', 'cancelled'], true)) {
            throw new Exception('Received or already cancelled purchase orders cannot be cancelled.', 422);
        }

        $order->update(['status' => 'cancelled']);

        return $order->fresh()->load('items.product', 'supplier');
    }

    public function getProcurementOverview(?string $branchId = null): array
    {
        $user = Auth::user();
        $resolved = $user ? EffectiveBranchScope::branchesFor($user) : null;

        $orderQuery = PurchaseOrder::query();
        $productQuery = Product::query()->where('status', 'active');

        if ($branchId) {
            if ($resolved !== null) {
                [, $branchIds] = $resolved;
                if (! in_array((int) $branchId, $branchIds, true)) {
                    throw new Exception('Branch is not within your allowed scope', 403);
                }
            }
            $orderQuery->where('business_branch_id', $branchId);
            $productQuery->where('business_branch_id', $branchId);
        } elseif ($resolved !== null) {
            [, $branchIds] = $resolved;
            $orderQuery->whereIn('business_branch_id', $branchIds);
            $productQuery->whereIn('business_branch_id', $branchIds);
        }

        $pendingOrders = (clone $orderQuery)->whereIn('status', ['draft', 'pending', 'approved', 'ordered'])->get();
        $recentlyReceived = (clone $orderQuery)->where('status', 'received')->orderByDesc('received_at')->limit(5)->get();
        $recentlyOrdered = (clone $orderQuery)->whereIn('status', ['ordered', 'partially_received', 'received'])->orderByDesc('order_date')->limit(5)->get();

        $lowStockProducts = (clone $productQuery)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->get();

        $suggestions = $this->reorderSuggestions($branchId);

        return [
            'products_needing_reorder' => $lowStockProducts->count(),
            'critical_stock' => (clone $productQuery)->where('quantity', '<=', 5)->count(),
            'suggested_purchase_value' => $suggestions['total_estimated_value'],
            'pending_purchase_orders' => $pendingOrders->count(),
            'pending_orders_value' => round($pendingOrders->sum('total_amount'), 2),
            'recently_ordered' => $recentlyOrdered,
            'recently_received' => $recentlyReceived,
            'reorder_suggestions' => $suggestions['suggestions'],
        ];
    }

    protected function resolveSupplier(Product $product): ?Supplier
    {
        return Supplier::query()
            ->where('business_id', $product->business_id ?? null)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->first();
    }
}
