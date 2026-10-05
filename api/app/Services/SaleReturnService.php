<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleReturnService
{
    protected CashFlowService $cashFlowService;

    protected InventoryService $inventoryService;

    protected CustomerCreditService $customerCreditService;

    public function __construct(CashFlowService $cashFlowService, InventoryService $inventoryService, CustomerCreditService $customerCreditService)
    {
        $this->cashFlowService = $cashFlowService;
        $this->inventoryService = $inventoryService;
        $this->customerCreditService = $customerCreditService;
    }

    public function handleCreateSaleReturn(array $validated, ?string $business_branch_id = null)
    {
        return DB::transaction(function () use ($validated, $business_branch_id) {
            $totalRefund = 0;
            $returnItems = [];

            $firstSaleItem = SaleItem::whereHas('sale')->with('product')->find($validated['items'][0]['sale_item_id'] ?? null);
            $sale = $firstSaleItem ? Sale::with('saleItems.product')->find($firstSaleItem->sale_id) : null;

            if (! $sale) {
                throw new Exception('Sale not found.', 404);
            }

            $branchId = $business_branch_id ?: $sale->business_branch_id;

            foreach ($validated['items'] as $item) {
                $saleItem = SaleItem::whereHas('sale')->with('product')->lockForUpdate()->find($item['sale_item_id']);
                if (! $saleItem) {
                    throw new Exception('Sale item not found.', 404);
                }

                if ($saleItem->sale_id !== $sale->id) {
                    throw new Exception('Sale item does not belong to this sale.', 404);
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
                'business_branch_id' => $branchId,
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
            $this->customerCreditService->recordRefund(Auth::user(), $sale, $totalRefund);

            $returnedQuantity = SaleReturnItem::whereIn('sale_item_id', $sale->saleItems->pluck('id'))
                ->sum('quantity');
            $soldQuantity = $sale->saleItems->sum('quantity');

            if ($returnedQuantity >= $soldQuantity) {
                Receipt::where('sale_id', $sale->id)
                    ->where('status', 'completed')
                    ->update(['status' => 'refunded']);
            }

            return $saleReturn->load(['saleReturnItems.saleItem.product', 'processedByUser']);
        });
    }

    public function handleUpdateSaleReturn(SaleReturn $saleReturn, array $validated): SaleReturn
    {
        return DB::transaction(function () use ($saleReturn, $validated) {
            $this->reverseSaleReturn($saleReturn);

            // reverseSaleReturn only undoes the side effects (stock, cash flow,
            // customer credit); the lines themselves are replaced by whatever this
            // edit says the return now covers. Leaving them behind would double
            // count the original return alongside its replacement.
            $saleReturn->saleReturnItems()->delete();

            $totalRefund = 0;
            $returnItems = [];

            $firstSaleItem = SaleItem::whereHas('sale')->with('product')->find($validated['items'][0]['sale_item_id'] ?? null);
            $sale = $firstSaleItem ? Sale::with('saleItems.product')->find($firstSaleItem->sale_id) : null;

            if (! $sale) {
                throw new Exception('Sale not found.', 404);
            }

            foreach ($validated['items'] as $item) {
                $saleItem = SaleItem::whereHas('sale')->with('product')->lockForUpdate()->find($item['sale_item_id']);
                if (! $saleItem) {
                    throw new Exception('Sale item not found.', 404);
                }

                if ($saleItem->sale_id !== $sale->id) {
                    throw new Exception('Sale item does not belong to this sale.', 404);
                }

                $alreadyReturned = SaleReturnItem::where('sale_item_id', $item['sale_item_id'])
                    ->where('sale_return_id', '!=', $saleReturn->id)
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

            $saleReturn->update([
                'reason' => $validated['reason'] ?? $saleReturn->reason,
                'notes' => $validated['notes'] ?? $saleReturn->notes,
                'refund_amount' => $totalRefund,
                'restock' => $validated['restock'] ?? $saleReturn->restock,
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
            $this->customerCreditService->recordRefund(Auth::user(), $sale, $totalRefund);

            $returnedQuantity = SaleReturnItem::whereIn('sale_item_id', $sale->saleItems->pluck('id'))
                ->sum('quantity');
            $soldQuantity = $sale->saleItems->sum('quantity');

            if ($returnedQuantity >= $soldQuantity) {
                Receipt::where('sale_id', $sale->id)
                    ->where('status', 'completed')
                    ->update(['status' => 'refunded']);
            } else {
                Receipt::where('sale_id', $sale->id)
                    ->where('status', 'refunded')
                    ->update(['status' => 'completed']);
            }

            return $saleReturn->load(['saleReturnItems.saleItem.product', 'processedByUser']);
        });
    }

    public function handleDeleteSaleReturn(SaleReturn $saleReturn): void
    {
        DB::transaction(function () use ($saleReturn) {
            $this->reverseSaleReturn($saleReturn);
            $saleReturn->saleReturnItems()->delete();
            $saleReturn->delete();
        });
    }

    protected function reverseSaleReturn(SaleReturn $saleReturn): void
    {
        $saleReturn->load(['saleReturnItems.saleItem.product']);

        $cashFlow = CashFlow::where('sale_return_id', $saleReturn->id)->first();
        if ($cashFlow) {
            // Force delete, not soft delete. transaction_code is derived from the
            // sale_return id and is unique, so the soft-deleted row would still
            // hold the code and re-creating the refund after an edit would fail
            // on a unique violation.
            $cashFlow->forceDelete();
        }

        foreach ($saleReturn->saleReturnItems as $item) {
            if ($saleReturn->restock && $item->saleItem) {
                $this->inventoryService->stockOut(
                    $item->saleItem->product,
                    $item->quantity,
                    'sale_return_reversal',
                    $saleReturn->id
                );
            }
        }

        $sale = Sale::find($saleReturn->saleReturnItems->first()?->saleItem?->sale_id);
        if ($sale) {
            $this->customerCreditService->recordRefund(Auth::user(), $sale, -$saleReturn->refund_amount);

            $returnedQuantity = SaleReturnItem::whereIn('sale_item_id', $sale->saleItems->pluck('id'))
                ->where('sale_return_id', '!=', $saleReturn->id)
                ->sum('quantity');
            $soldQuantity = $sale->saleItems->sum('quantity');

            if ($returnedQuantity < $soldQuantity) {
                Receipt::where('sale_id', $sale->id)
                    ->where('status', 'refunded')
                    ->update(['status' => 'completed']);
            }
        }
    }
}
