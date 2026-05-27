<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class CashFlowService
{
     /**
     * Create CashFlow record for this sale
     */
    public function createCashFlowForSale(Sale $sale, float $amount, array $validated, string $method): void
    {
      $user = Auth::user();
        CashFlow::create([
            'transaction_code' => $validated["transaction_code"] ?? 'CF-SALE-'.str_pad($sale->id, 6, '0'.STR_PAD_LEFT),
            'type' => 'sale',
            'amount' => $amount,
            'currency' => $validated['currency'] ?? 'UGX',
            'business_id' => $user->business_id,
            'business_branch_id' => $sale->business_branch_id,
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'description' => $sale->note ?? "Walk-in sale",
            'category' => 'product_sales',
            'payment_method' => $method ?? 'cash',
            'reference' => $validated['reference'] ?? null,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow purchase ====================
    public function createCashFlowForPurchase(Purchase $purchase, float $amount, array $validated, string $method): void
    {
      $user = Auth::user();
        CashFlow::create([
            'transaction_code' => $validated["transaction_code"] ?? 'CF-PURCH-'.str_pad($purchase->id, 6, '0'.STR_PAD_LEFT),
            'type' => 'purchase',
            'amount' => $amount,
            'currency' => $validated['currency'] ?? 'UGX',
            'business_id' => $user->business_id,
            'business_branch_id' => $purchase->business_branch_id,
            // 'customer_id' => $purchase->customer_id,
            'purchase_id' => $purchase->id,
            'description' => $purchase->note ?? "Walk-in purchase",
            'category' => 'product_sales',
            'payment_method' => $method ?? 'cash',
            'reference' => $validated['reference'] ?? null,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }
}