<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Receipt;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Exception;
use Illuminate\Support\Facades\Auth;

class SaleReturnService
{
    protected CashFlowService $cashFlowService;
    protected InventoryService $inventoryService;

    public function __construct(CashFlowService $cashFlowService, InventoryService $inventoryService)
    {
        $this->cashFlowService = $cashFlowService;
        $this->inventoryService = $inventoryService;
    }

    public function handleCreateSaleReturn(array $validated, string $business_branch_id)
    {
        $totalRefund = 0;
        $returnItems = [];

        foreach ($validated['items'] as $item) {
            $saleItem = SaleItem::with('product')->find($item['sale_item_id']);
            if (!$saleItem) {
                throw new Exception("Sale item not found.", 404);
            }

            $alreadyReturned = SaleReturnItem::where('sale_item_id', $item['sale_item_id'])
                ->sum('quantity');

            $available = $saleItem->quantity - $alreadyReturned;
            if ($item['quantity'] > $available) {
                throw new Exception(
                    "Cannot return more than {$available} of this product.",
                    422
                );
            }

            $subtotal = $item['quantity'] * $saleItem->unit_price;
            $totalRefund += $subtotal;

            $returnItems[] = [
                'sale_item_id' => $item['sale_item_id'],
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
                'condition' => $item['condition'] ?? null,
                'product' => $saleItem->product,
            ];
        }

        $saleReturn = SaleReturn::create([
            'business_branch_id' => $business_branch_id,
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'refund_amount' => $totalRefund,
            'restock' => $validated['restock'] ?? true,
            'processed_by' => Auth::id(),
            'status' => 'completed',
        ]);

        foreach ($returnItems as $ri) {
            SaleReturnItem::create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => $ri['sale_item_id'],
                'quantity' => $ri['quantity'],
                'subtotal' => $ri['subtotal'],
                'condition' => $ri['condition'],
            ]);

            if ($saleReturn->restock) {
                $this->inventoryService->stockIn(
                    $ri['product'],
                    $ri['quantity'],
                    'sale_return',
                    $saleReturn->id
                );
            }
        }

        $this->cashFlowService->createCashFlowForSaleReturn($saleReturn, $totalRefund, $validated);

        $firstSaleItem = SaleItem::find($validated['items'][0]['sale_item_id']);
        if ($firstSaleItem) {
            Receipt::where('sale_id', $firstSaleItem->sale_id)
                ->where('status', 'completed')
                ->update(['status' => 'refunded']);
        }

        return $saleReturn->load(['saleReturnItems.saleItem.product', 'processedByUser']);
    }
}
