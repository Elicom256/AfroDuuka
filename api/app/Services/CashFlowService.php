<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\EmployeeRemuneration;
use App\Models\Expense;
use App\Models\BusinessTaxPayment;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
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

    // ================= cash flow worker payment (outflow) ====================
    public function createCashFlowForWorkerPayment(EmployeeRemuneration $remuneration, float $amount): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $currency = $business?->country?->currency_code ?? 'UGX';
        CashFlow::create([
            'transaction_code' => 'CF-WORKER-'.str_pad($remuneration->id, 6, '0', STR_PAD_LEFT),
            'type' => 'expense',
            'amount' => $amount,
            'currency' => $currency,
            'business_id' => $user->business_id,
            'business_branch_id' => $remuneration->business_branch_id ?? $user->business_branch_id,
            'description' => $remuneration->description ?? 'Worker payment',
            'category' => 'worker_payments',
            'reference' => $remuneration->reference ?? null,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow tax payment (outflow) ====================
    public function createCashFlowForTaxPayment(BusinessTaxPayment $payment, float $amount): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $currency = $business?->country?->currency_code ?? 'UGX';
        CashFlow::create([
            'transaction_code' => 'CF-TAX-'.str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            'type' => 'expense',
            'amount' => $amount,
            'currency' => $currency,
            'business_id' => $user->business_id,
            'business_branch_id' => $payment->business_branch_id,
            'tax_payment_id' => $payment->id,
            'description' => 'Tax payment for period '.($payment->tax_period ?? 'N/A'),
            'category' => 'tax_payments',
            'reference' => $payment->reference_number ?? null,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow stock transfer dispatch (outflow on sending branch) ====================
    public function createCashFlowForStockTransferDispatch(StockTransfer $transfer, float $totalCost): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $currency = $business?->country?->currency_code ?? 'UGX';
        CashFlow::create([
            'transaction_code' => 'CF-STOCK-OUT-'.str_pad($transfer->id, 6, '0', STR_PAD_LEFT),
            'type' => 'expense',
            'amount' => $totalCost,
            'currency' => $currency,
            'business_id' => $transfer->business_id,
            'business_branch_id' => $transfer->from_branch_id,
            'stock_transfer_id' => $transfer->id,
            'description' => 'Stock transfer dispatch to branch #'.$transfer->to_branch_id,
            'category' => 'stock_transfer',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow stock transfer receive (inflow on receiving branch) ====================
    public function createCashFlowForStockTransferReceive(StockTransfer $transfer, float $totalCost): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $currency = $business?->country?->currency_code ?? 'UGX';
        CashFlow::create([
            'transaction_code' => 'CF-STOCK-IN-'.str_pad($transfer->id, 6, '0', STR_PAD_LEFT),
            'type' => 'payment_in',
            'amount' => $totalCost,
            'currency' => $currency,
            'business_id' => $transfer->business_id,
            'business_branch_id' => $transfer->to_branch_id,
            'stock_transfer_id' => $transfer->id,
            'description' => 'Stock transfer receive from branch #'.$transfer->from_branch_id,
            'category' => 'stock_transfer',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow sale return (outflow: refund to customer) ====================
    public function createCashFlowForSaleReturn(SaleReturn $saleReturn, float $amount, array $validated): void
    {
        $user = Auth::user();
        CashFlow::create([
            'transaction_code' => 'CF-SR-'.str_pad($saleReturn->id, 6, '0', STR_PAD_LEFT),
            'type' => 'refund',
            'amount' => $amount,
            'currency' => 'UGX',
            'business_id' => $user->business_id,
            'business_branch_id' => $saleReturn->business_branch_id,
            'sale_return_id' => $saleReturn->id,
            'description' => $saleReturn->reason ?? 'Sale return refund',
            'category' => 'product_sales',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow purchase return (inflow: money back from supplier) ====================
    public function createCashFlowForPurchaseReturn(PurchaseReturn $purchaseReturn, float $amount, array $validated): void
    {
        $user = Auth::user();
        CashFlow::create([
            'transaction_code' => 'CF-PR-'.str_pad($purchaseReturn->id, 6, '0', STR_PAD_LEFT),
            'type' => 'payment_in',
            'amount' => $amount,
            'currency' => 'UGX',
            'business_id' => $user->business_id,
            'business_branch_id' => $purchaseReturn->business_branch_id,
            'supplier_id' => $purchaseReturn->supplier_id ?? null,
            'purchase_return_id' => $purchaseReturn->id,
            'description' => $purchaseReturn->reason ?? 'Purchase return from supplier',
            'category' => 'product_sales',
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    // ================= cash flow expense ====================
    public function createCashFlowForExpense(Expense $expense, float $amount): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $currency = $business?->country?->currency_code ?? 'UGX';
        CashFlow::create([
            'transaction_code' => 'CF-EXP-'.str_pad($expense->id, 6, '0', STR_PAD_LEFT),
            'type' => 'expense',
            'amount' => $amount,
            'currency' => $currency,
            'business_id' => $user->business_id,
            'business_branch_id' => $expense->business_branch_id ?? $user->business_branch_id,
            'expense_id' => $expense->id,
            'description' => $expense->description ?? $expense->category?->name ?? 'General expense',
            'category' => 'expenses',
            'reference' => null,
            'status' => 'completed',
            'transaction_date' => $expense->payment_date->toDateString(),
            'created_by' => $user->id,
        ]);
    }

    public function analytics(string $business_branch_id, string $period = "last_7_days"){
        $dates = $this->analyticsTrendHelper->getPeriodDates($period);
        $date_range = [$dates["start"], $dates["end"]];
        // ✅ Total Revenue (inflows: sale + payment_in + refund)
        $totalRevenue = CashFlow::where("business_branch_id", $business_branch_id)
                                 ->whereIn("type", ["sale", "payment_in", "refund"])
                                 ->whereBetween("created_at", $date_range)
                                 ->sum("amount");

        // ✅ Total Expenses (outflows: purchase + expense + payment_out)
        $totalExpenses = CashFlow::where("business_branch_id", $business_branch_id)
                        ->whereIn("type", ["purchase", "expense", "payment_out"])
                        ->whereBetween("created_at", $date_range)
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