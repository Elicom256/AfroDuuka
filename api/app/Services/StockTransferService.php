<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    protected CashFlowService $cashFlowService;

    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
    }

    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $transfer = StockTransfer::create($data);
            foreach ($items as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_expected' => $item['quantity_expected'],
                    'quantity_received' => $item['quantity_received'] ?? null,
                    'status' => $item['status'] ?? 'pending',
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function dispatch(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new \Exception('Only draft transfers can be dispatched.');
        }

        return DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $sourceProduct = Product::where('business_branch_id', $transfer->from_branch_id)
                    ->where('id', $item->product_id)
                    ->firstOrFail();
                $destProduct = Product::where('business_branch_id', $transfer->to_branch_id)
                    ->where('product_category_id', $sourceProduct->product_category_id)
                    ->firstOrFail();

                if ($sourceProduct->quantity < $item->quantity_expected) {
                    throw new \Exception("Insufficient stock for product: {$sourceProduct->name}");
                }
                if (!$destProduct) {
                    throw new \Exception("Product does not exist on receiver branch");
                }

                $sourceProduct->decrement('quantity', $item->quantity_expected);
                $destProduct->increment('quantity', $item->quantity_expected);

                StockMovement::create([
                    'business_id' => $transfer->business_id,
                    'business_branch_id' => $transfer->from_branch_id,
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'quantity' => $item->quantity_expected,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Stock transfer to branch #{$transfer->to_branch_id}",
                ]);
            }

            $transfer->update([
                'status' => 'in_transit',
                'dispatched_at' => now(),
            ]);

            $totalCost = $transfer->items->sum(function ($item) {
                return ($item->product?->cost_price ?? 0) * $item->quantity_expected;
            });
            $this->cashFlowService->createCashFlowForStockTransferDispatch($transfer, $totalCost);

            return $transfer->fresh()->load('items');
        });
    }

    public function receive(StockTransfer $transfer, array $receivedItems): StockTransfer
    {
        if ($transfer->status !== 'in_transit') {
            throw new \Exception('Only in-transit transfers can be received.');
        }

        return DB::transaction(function () use ($transfer, $receivedItems) {
            $user = Auth::user();

            foreach ($transfer->items as $item) {
                $receivedQty = $receivedItems[$item->id] ?? $item->quantity_expected;

                $destProduct = Product::firstOrCreate(
                    [
                        'business_branch_id' => $transfer->to_branch_id,
                        'product_category_id' => $item->product->product_category_id,
                    ],
                    [
                        'name' => $item->product->name,
                        'cost_price' => $item->product->cost_price,
                        'price' => $item->product->price,
                        'quantity' => 0,
                    ]
                );

                $destProduct->increment('quantity', $receivedQty);

                $item->update([
                    'quantity_received' => $receivedQty,
                    'status' => $receivedQty === $item->quantity_expected ? 'received' : 'damaged',
                ]);

                StockMovement::create([
                    'business_id' => $transfer->business_id,
                    'business_branch_id' => $transfer->to_branch_id,
                    'product_id' => $destProduct->id,
                    'type' => 'in',
                    'quantity' => $receivedQty,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Stock transfer from branch #{$transfer->from_branch_id}",
                ]);
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            $totalCost = $transfer->items->sum(function ($item) use ($receivedItems) {
                return ($item->product?->cost_price ?? 0) * ($receivedItems[$item->id] ?? $item->quantity_expected);
            });
            $this->cashFlowService->createCashFlowForStockTransferReceive($transfer, $totalCost);

            return $transfer->fresh()->load('items');
        });
    }

    public function cancel(StockTransfer $transfer): StockTransfer
    {
        if (!in_array($transfer->status, ['draft', 'in_transit'])) {
            throw new \Exception('Only draft or in-transit transfers can be cancelled.');
        }

        $transfer->update(['status' => 'cancelled']);

        return $transfer->fresh();
    }
}
