<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnService
{
    protected CashFlowService $cashFlowService;
    protected InventoryService $inventoryService;

    public function __construct(CashFlowService $cashFlowService, InventoryService $inventoryService)
    {
        $this->cashFlowService = $cashFlowService;
        $this->inventoryService = $inventoryService;
    }

    public function handleCreatePurchaseReturn(array $validated)
    {
        $totalRefund = 0;
        $returnItems = [];

        foreach ($validated['items'] as $item) {
            $purchaseItem = PurchaseItem::with('product')->find($item['purchase_item_id']);
            if (!$purchaseItem) {
                throw new Exception("Purchase item not found.", 404);
            }

            $alreadyReturned = PurchaseReturnItem::where('purchase_item_id', $item['purchase_item_id'])
                ->sum('quantity');

            $available = $purchaseItem->quantity - $alreadyReturned;
            if ($item['quantity'] > $available) {
                throw new Exception(
                    "Cannot return more than {$available} of this product.",
                    422
                );
            }

            $subtotal = $item['quantity'] * $purchaseItem->cost_price;
            $totalRefund += $subtotal;

            $returnItems[] = [
                'purchase_item_id' => $item['purchase_item_id'],
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
                'condition' => $item['condition'] ?? null,
                'product' => $purchaseItem->product,
            ];
        }

        $purchaseReturn = PurchaseReturn::create([
            'business_branch_id' => $validated['business_branch_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'refund_amount' => $totalRefund,
            'restock' => $validated['restock'] ?? false,
            'processed_by' => Auth::id(),
            'status' => 'completed',
        ]);

        foreach ($returnItems as $ri) {
            PurchaseReturnItem::create([
                'purchase_return_id' => $purchaseReturn->id,
                'purchase_item_id' => $ri['purchase_item_id'],
                'quantity' => $ri['quantity'],
                'subtotal' => $ri['subtotal'],
                'condition' => $ri['condition'],
            ]);

            if (!$purchaseReturn->restock) {
                $this->inventoryService->stockOut(
                    $ri['product'],
                    $ri['quantity'],
                    'purchase_return',
                    $purchaseReturn->id
                );
            }
        }

        $this->cashFlowService->createCashFlowForPurchaseReturn($purchaseReturn, $totalRefund, $validated);

        return $purchaseReturn->load(['purchaseReturnItems.purchaseItem.product', 'supplier', 'processedByUser']);
    }
}
