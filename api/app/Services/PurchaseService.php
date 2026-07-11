<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PurchaseService
{
    protected CashFlowService $cashFlowService;
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(CashFlowService $cashFlowService, AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->cashFlowService = $cashFlowService;
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    public function savePurchase($validated)
    {
        $notificationService = app(NotificationService::class);
        $user = Auth::user();
        $total_amount = collect($validated["items"])->sum(fn($i) => $i["cost_price"] * $i["quantity"]);
        $purchase = Purchase::create([
            "supplier_id" => $validated["supplier_id"],
            "business_branch_id" => $validated["business_branch_id"],
            "total_amount" => $total_amount,
            "note" => $validated["note"] ?? null
        ]);
        foreach ($validated["items"] as $item) {
            PurchaseItem::create([
                "purchase_id" => $purchase->id,
                "product_id" => $item["product_id"],
                "quantity" => $item["quantity"],
                "cost_price" => $item["cost_price"],
                "subtotal" => $item["cost_price"] * $item["quantity"]
            ]);
            $businessProduct = Product::find($item["product_id"]);
            if ($businessProduct) {
                $price = $item["cost_price"] * (1 + $businessProduct->markup_percentage) ?? $businessProduct->price;
                $businessProduct->increment("quantity", $item["quantity"]);
                $businessProduct->update([
                    "cost_price" => $item["cost_price"],
                    "price" => $price,
                ]);
            }
        }
        $supplier = Supplier::find($purchase->supplier_id);
        $this->cashFlowService->createCashFlowForPurchase($purchase, $total_amount, $validated);
        $notificationService->newPurchaseRecorded($user, $supplier->company_name, number_format($total_amount));

        return $purchase->load("purchaseItems");
    }

    public function analytics(string $period = "last_7_days")
    {
        $user = Auth::user();
        $branchId = $user->business_branch_id;

        $query = Purchase::where('business_branch_id', $branchId)
                     ->where('status', 'completed');

        $days = $this->analyticsTrendHelper->getDaysFromPeriod($period);

        $query->where('created_at', '>=', Carbon::now()->subDays($days - 1));
        $purchases = $query->get();
        $totalPurchases = $purchases->sum("total_amount");
        $totalTransactions = $purchases->count();
        $avgPurchases = $totalPurchases ? $totalPurchases / $totalTransactions : 0;
        $testAvg = $purchases->average("total_amount");

        $purchaseTrend = $purchases->groupBy(function ($purchase) {
            return Carbon::parse($purchase->created_at)->format("M d");
        })->map(function ($group) {
            return [
                'date'   => $group->first()->created_at->format('M d'),
                'amount' => $group->sum('total_amount'),
                'count'  => $group->count(),
            ];
        })->values();

        $purchasesTrend = $this->analyticsTrendHelper->fillMissingDates($purchaseTrend, $days);

        return [
            'total_purchases'        => round($totalPurchases, 2),
            'avg_purchase'           => round($avgPurchases, 2),
            'test_avg'               => round($testAvg, 2),
            'total_transactions'     => $totalTransactions,
            'purchase_trend'         => $purchasesTrend,
            'period'                 => $period,
            "lable"                  => "purchases"
        ];
    }
}
