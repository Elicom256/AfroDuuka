<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Purchase;

class SupplierPurchases extends Tool
{
    public function name(): string
    {
        return 'supplier_purchases';
    }

    public function description(): string
    {
        return 'Get purchase history from a specific supplier';
    }

    public function parameters(): array
    {
        return [
            'supplier_id' => [
                'type' => 'integer',
                'description' => 'The supplier ID to look up',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of purchases to return (default 20)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $supplierId = $parameters['supplier_id'] ?? null;
        $limit = min((int) ($parameters['limit'] ?? 20), 100);

        if (!$supplierId) {
            return ['message' => 'Please provide a supplier ID.'];
        }

        $supplier = \App\Models\Supplier::with('user')->find($supplierId);

        if (!$supplier) {
            return ['message' => 'Supplier not found.'];
        }

        $purchases = Purchase::where('supplier_id', $supplierId)
            ->with('purchaseItems.product', 'businessBranch')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($purchase) => [
                'id' => $purchase->id,
                'total_amount' => $purchase->total_amount,
                'status' => $purchase->status,
                'branch' => $purchase->businessBranch?->name,
                'items_count' => $purchase->purchaseItems->count(),
                'date' => $purchase->created_at->toDateTimeString(),
            ]);

        $totalSpent = (float) Purchase::where('supplier_id', $supplierId)
            ->where('status', 'completed')
            ->sum('total_amount');

        return [
            'supplier_name' => $supplier->user?->name ?? $supplier->company_name ?? "Supplier #{$supplierId}",
            'supplier_code' => $supplier->supplier_code,
            'total_purchases' => $purchases->count(),
            'total_spent' => $totalSpent,
            'recent_purchases' => $purchases,
        ];
    }
}
