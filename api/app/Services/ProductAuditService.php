<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAudit;
use App\Models\ProductAuditItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductAuditService
{
    protected InventoryService $inventoryService;

    protected ActivityLogService $activityLog;

    public function __construct(InventoryService $inventoryService, ActivityLogService $activityLog)
    {
        $this->inventoryService = $inventoryService;
        $this->activityLog = $activityLog;
    }

    public function generateAuditNumber(int $businessBranchId): string
    {
        $count = ProductAudit::where('business_branch_id', $businessBranchId)->count() + 1;

        return 'PAUDIT-'.str_pad($businessBranchId, 4, '0', STR_PAD_LEFT).'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function createAudit(array $data, array $items): ProductAudit
    {
        return DB::transaction(function () use ($data, $items) {
            $audit = ProductAudit::create($data);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $systemQty = $product->quantity;
                $countedQty = $item['counted_quantity'];
                $difference = $countedQty - $systemQty;

                ProductAuditItem::create([
                    'product_audit_id' => $audit->id,
                    'product_id' => $product->id,
                    'system_quantity' => $systemQty,
                    'counted_quantity' => $countedQty,
                    'difference' => $difference,
                    'adjustment_quantity' => $item['adjustment_quantity'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $audit->load(['items.product', 'branch', 'performedBy']);
        });
    }

    /**
     * The audit trail is written inside the transaction on purpose: a log entry recording
     * an approval that then failed to commit is worse than no entry at all. This call
     * used to call a static ActivityLog::log() that does not exist, so it threw, and
     * because it threw in here every stock adjustment below it rolled back with it --
     * approving a product audit silently did nothing.
     */
    public function approveAudit(ProductAudit $audit): ProductAudit
    {
        return DB::transaction(function () use ($audit) {
            $audit->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            foreach ($audit->items as $item) {
                if ($item->difference === 0) {
                    continue;
                }

                $product = $item->product;

                $this->inventoryService->adjust(
                    $product,
                    $item->difference,
                    "Stock audit adjustment - audit #{$audit->audit_number}"
                );

                $lastMovement = StockMovement::where('product_id', $product->id)
                    ->where('type', 'adjustment')
                    ->latest()
                    ->first();

                if ($lastMovement) {
                    $lastMovement->update([
                        'reference_type' => ProductAudit::class,
                        'reference_id' => $audit->id,
                    ]);
                }
            }

            $this->activityLog->activity(
                'approved_product_audit',
                "Approved product audit #{$audit->audit_number}",
                subject: $audit,
            );

            return $audit->fresh(['items.product', 'branch', 'performedBy', 'approvedBy']);
        });
    }

    public function cancelAudit(ProductAudit $audit): ProductAudit
    {
        $audit->update(['status' => 'cancelled']);

        $this->activityLog->activity(
            'cancelled_product_audit',
            "Cancelled product audit #{$audit->audit_number}",
            subject: $audit,
        );

        return $audit->fresh(['items.product', 'branch', 'performedBy']);
    }
}
