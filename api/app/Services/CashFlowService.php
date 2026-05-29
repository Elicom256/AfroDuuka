<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CashFlowService
{
     protected AnalyticsTrendHelper $analyticsTrendHelper;
    public function __construct( AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }
     /**
     * Create CashFlow record for this sale
     */
    public function createCashFlowForSale(Sale $sale, float $amount, array $validated): void
    {
      $user = Auth::user();
        CashFlow::create([
            'transaction_code' => $validated["transaction_code"] ?? 'CF-SALE-'.str_pad($sale->id, 6, '0'.STR_PAD_LEFT),
            'type' => 'sale',
            'amount' => $amount,
            'currency' => $validated['currency'] ?? 'UGX',
            'business_id' => $user->business_id,
            'business_branch_id' => $sale->business_branch_id,
            'customer_id' => $sale->customer_id ?? null,
            'sale_id' => $sale->id,
            'description' => $sale->note ?? "Walk-in sale",
            'category' => 'product_sales',
            'payment_status_id' => $validated["payment_status_id"],
            'reference' => $validated['reference'] ?? null,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow purchase ====================
    public function createCashFlowForPurchase(Purchase $purchase, float $amount, array $validated): void
    {
      $user = Auth::user();
        CashFlow::create([
            'transaction_code' => $validated["transaction_code"] ?? 'CF-PURCH-'.str_pad($purchase->id, 6, '0'.STR_PAD_LEFT),
            'type' => 'purchase',
            'amount' => $amount,
            'currency' => $validated['currency'] ?? 'UGX',
            'business_id' => $user->business_id,
            'business_branch_id' => $purchase->business_branch_id,
            'supplier_id' => $purchase->supplier_id ?? null,
            'purchase_id' => $purchase->id,
            'description' => $purchase->note ?? "Walk-in purchase",
            'category' => 'product_sales',
            'payment_status_id' => $validated["payment_status_id"],
            'reference' => $validated['reference'] ?? null,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

public function analytics(string $business_branch_id, string $period = "last_7_days"){
    $dates = $this->analyticsTrendHelper->getPeriodDates($period);
    $date_range = [$dates["start"], $dates["end"]];
    // ✅ Total Revenue
    $totalRevenue = CashFlow::where("business_branch_id", $business_branch_id)
                             ->where("type", "sale")
                             ->where("created_at", ">=", $date_range)
                             ->sum("amount");

    // ✅ Total Expenses
    $totalExpenses = CashFlow::where("business_branch_id", $business_branch_id)
                    ->where("type", "purchase")
                    ->where("created_at", ">=", $date_range)
                    ->sum("amount");  
    //  ✅ Net Cash Flow
     $netCashFlow = $totalRevenue - $totalExpenses; 

     return [
        "total_revenue" => $totalRevenue,
        "total_expenses" => $totalExpenses,
        "net_cash_flow" => $netCashFlow
    ];
}
}