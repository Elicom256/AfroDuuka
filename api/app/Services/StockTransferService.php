<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Database\UniqueConstraintViolationException;
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
            // Re-read the transfer under a row lock. The status check above ran outside
            // the transaction, so two dispatchers racing on the same draft both saw
            // 'draft' and both went on to decrement the source branch — the second one
            // silently double-counting the movement out.
            $transfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== 'draft') {
                throw new \Exception('Only draft transfers can be dispatched.');
            }

            $transfer->load('items');

            foreach ($transfer->items as $item) {
                // lockForUpdate, matching PosService::checkout(): reading quantity and
                // then decrementing it is a read-modify-write, and two concurrent
                // dispatches of the same product both read the pre-transfer quantity
                // and wrote back a decrement from that stale number.
                $sourceProduct = Product::where('business_branch_id', $transfer->from_branch_id)
                    ->where('id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($sourceProduct->quantity < $item->quantity_expected) {
                    throw new \Exception("Insufficient stock for product: {$sourceProduct->name}");
                }

                $destProduct = $this->resolveDestinationProduct($sourceProduct, $transfer->to_branch_id);

                $sourceProduct->decrement('quantity', $item->quantity_expected);
                $destProduct->increment('quantity', $item->quantity_expected);

                StockMovement::updateOrCreate(
                    [
                        'movement_key' => $this->stockMovementKey('out', (int) $item->product_id, (int) $transfer->id, StockTransfer::class, (int) $item->quantity_expected),
                    ],
                    [
                        'business_id' => $transfer->business_id,
                        'business_branch_id' => $transfer->from_branch_id,
                        'product_id' => $item->product_id,
                        'type' => 'out',
                        'quantity' => $item->quantity_expected,
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'notes' => "Stock transfer to branch #{$transfer->to_branch_id}",
                    ]
                );
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

            // Same reason as dispatch(): the status check ran outside the transaction,
            // so a double-tap on "receive" could pass it twice and add the incoming
            // stock to the destination branch a second time.
            $transfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== 'in_transit') {
                throw new \Exception('Only in-transit transfers can be received.');
            }

            $transfer->load('items');

            foreach ($transfer->items as $item) {
                $receivedQty = $receivedItems[$item->id] ?? $item->quantity_expected;

                $destProduct = $this->resolveDestinationProduct($item->product, $transfer->to_branch_id);

                $destProduct->increment('quantity', $receivedQty);

                $item->update([
                    'quantity_received' => $receivedQty,
                    'status' => $receivedQty === $item->quantity_expected ? 'received' : 'damaged',
                ]);

                StockMovement::updateOrCreate(
                    [
                        'movement_key' => $this->stockMovementKey('in', (int) $destProduct->id, (int) $transfer->id, StockTransfer::class, (int) $receivedQty),
                    ],
                    [
                        'business_id' => $transfer->business_id,
                        'business_branch_id' => $transfer->to_branch_id,
                        'product_id' => $destProduct->id,
                        'type' => 'in',
                        'quantity' => $receivedQty,
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'notes' => "Stock transfer from branch #{$transfer->from_branch_id}",
                    ]
                );
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

    protected function stockMovementKey(string $type, int $productId, int $referenceId, string $referenceType, int $quantity): string
    {
        return md5($referenceType.':'.$referenceId.':'.$productId.':'.$type.':'.$quantity);
    }

    public function cancel(StockTransfer $transfer): StockTransfer
    {
        if (! in_array($transfer->status, ['draft', 'in_transit'])) {
            throw new \Exception('Only draft or in-transit transfers can be cancelled.');
        }

        $transfer->update(['status' => 'cancelled']);

        return $transfer->fresh();
    }

    /**
     * Find (or create) the product on the destination branch that corresponds
     * to the source product. Products are stored per-branch, so a transfer must
     * match by product identity (sku -> barcode -> name) instead of category,
     * which could redirect stock to an unrelated product of the same category.
     */
    private function resolveDestinationProduct(Product $sourceProduct, int $toBranchId): Product
    {
        $base = Product::withoutGlobalScope('branch')->where('business_branch_id', $toBranchId);

        $match = null;
        if ($sourceProduct->sku) {
            $match = (clone $base)->where('sku', $sourceProduct->sku)->lockForUpdate()->first();
        }
        if (! $match && $sourceProduct->barcode) {
            $match = (clone $base)->where('barcode', $sourceProduct->barcode)->lockForUpdate()->first();
        }
        if (! $match) {
            $match = (clone $base)->where('name', $sourceProduct->name)->lockForUpdate()->first();
        }

        if ($match) {
            return $match;
        }

        // No unique index exists on (business_branch_id, sku), so the lock above
        // cannot stop two concurrent transfers of the same product from both finding
        // nothing and both inserting a destination row. That is the "Unique violation
        // on product name during transfer" report: two rows for one product, and stock
        // split across them depending on which request won.
        //
        // Rather than depend on an index that a legacy database may not have yet, catch
        // the collision and re-read. The loser of the insert race finds the row the
        // winner just committed and increments that one instead.
        try {
            return Product::create([
                'business_branch_id' => $toBranchId,
                'product_category_id' => $sourceProduct->product_category_id,
                'tax_category_id' => $sourceProduct->tax_category_id,
                'name' => $sourceProduct->name,
                'sku' => $sourceProduct->sku,
                'barcode' => $sourceProduct->barcode,
                'quantity' => 0,
                'cost_price' => $sourceProduct->cost_price,
                'selling_price' => $sourceProduct->selling_price,
                'is_tax_inclusive' => $sourceProduct->is_tax_inclusive,
                'reorder_level' => $sourceProduct->reorder_level,
                'description' => $sourceProduct->description,
                'status' => $sourceProduct->status,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $existing = (clone $base)->where('name', $sourceProduct->name)->lockForUpdate()->first();

            if ($existing === null) {
                throw $e;
            }

            return $existing;
        }
    }
}
