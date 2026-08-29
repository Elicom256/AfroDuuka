<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAudit;
use App\Models\ProductAuditItem;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductAuditService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function generateAuditNumber(int $businessBranchId): string
    {
        $count = ProductAudit::where('business_branch_id', $businessBranchId)->count() + 1;
        return 'PAUDIT-' . str_pad($businessBranchId, 4, '0', STR_PAD_LEFT) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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

            ActivityLog::log(
                Auth::user(),
                'approved_product_audit',
                $audit,
                "Approved product audit #{$audit->audit_number}"
            );

            return $audit->fresh(['items.product', 'branch', 'performedBy', 'approvedBy']);
        });
    }

    public function cancelAudit(ProductAudit $audit): ProductAudit
    {
        $audit->update(['status' => 'cancelled']);

        ActivityLog::log(
            Auth::user(),
            'cancelled_product_audit',
            $audit,
            "Cancelled product audit #{$audit->audit_number}"
        );

        return $audit->fresh(['items.product', 'branch', 'performedBy']);
    }
}
