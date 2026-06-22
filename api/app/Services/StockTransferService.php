<?php

namespace App\Services;

use App\Models\BusinessBranchProduct;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles stock transfer business logic:
 * dispatching stock from source branch and receiving into destination branch.
 */
class StockTransferService
{
    /**
     * Create a stock transfer with its line items.
     */
    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $transfer = StockTransfer::create($data);
            foreach ($items as $item) {
            $transfer->items()->create([
                'business_branch_product_id' => $item['business_branch_product_id'],
                'quantity_expected' => $item['quantity_expected'],
                'quantity_received' => $item['quantity_received'] ?? null,
                'status' => $item['status'] ?? 'pending',
            ]);
            }


            return $transfer->load('items');
        });
    }

    /**
     * Dispatch a transfer: decrement source branch stock, mark as in_transit.
     */
    public function dispatch(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new \Exception('Only draft transfers can be dispatched.');
        }

        return DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $branchProduct = BusinessBranchProduct::where('business_branch_id', $transfer->from_branch_id)
                    ->where('id', $item->business_branch_product_id)
                    ->firstOrFail();
                $receiverBranchPdt = BusinessBranchProduct::where('business_branch_id', $transfer->to_branch_id)
                    ->where('product_id', $branchProduct->product_id)
                    ->firstOrFail();

                if ($branchProduct->quantity < $item->quantity_expected) {
                    throw new \Exception("Insufficient stock for product: {$branchProduct->name}");
                }
                if(!$receiverBranchPdt){
                    throw new \Exception("Product does not exist on receiver branch");
                }

                // Decrement source branch stock
                $branchProduct->decrement('quantity', $item->quantity_expected);
                // Increment the products at the receiving branch
                $receiverBranchPdt->increment('quantity', $item->quantity_expected);

                // Record stock movement (out)
                StockMovement::create([
                    'business_id' => $transfer->business_id,
                    'business_branch_id' => $transfer->from_branch_id,
                    'business_branch_product_id' => $item->business_branch_product_id,
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

            return $transfer->fresh()->load('items');
        });
    }

    /**
     * Receive a transfer: increment destination branch stock, mark as received.
     */
    public function receive(StockTransfer $transfer, array $receivedItems): StockTransfer
    {
        if ($transfer->status !== 'in_transit') {
            throw new \Exception('Only in-transit transfers can be received.');
        }

        return DB::transaction(function () use ($transfer, $receivedItems) {
            $user = Auth::user();

            foreach ($transfer->items as $item) {
                $receivedQty = $receivedItems[$item->id] ?? $item->quantity_expected;

                // Find or create the product in destination branch
                $destProduct = BusinessBranchProduct::firstOrCreate(
                    [
                        'business_branch_id' => $transfer->to_branch_id,
                        'product_id' => $item->businessBranchProduct->product_id,
                    ],
                    [
                        'name' => $item->businessBranchProduct->name,
                        'cost_price' => $item->businessBranchProduct->cost_price,
                        'price' => $item->businessBranchProduct->price,
                        'quantity' => 0,
                    ]
                );

                $destProduct->increment('quantity', $receivedQty);

                $item->update([
                    'quantity_received' => $receivedQty,
                    'status' => $receivedQty === $item->quantity_expected ? 'received' : 'damaged',
                ]);

                // Record stock movement (in)
                StockMovement::create([
                    'business_id' => $transfer->business_id,
                    'business_branch_id' => $transfer->to_branch_id,
                    'business_branch_product_id' => $destProduct->id,
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

            return $transfer->fresh()->load('items');
        });
    }

    /**
     * Cancel a draft transfer.
     */
    public function cancel(StockTransfer $transfer): StockTransfer
    {
        if (!in_array($transfer->status, ['draft', 'in_transit'])) {
            throw new \Exception('Only draft or in-transit transfers can be cancelled.');
        }

        $transfer->update(['status' => 'cancelled']);

        return $transfer->fresh();
    }
}
