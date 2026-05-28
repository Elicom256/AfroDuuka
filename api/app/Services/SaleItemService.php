<?php

namespace App\Services;

use App\Models\BusinessBranchProduct;
use App\Models\CashFlow;
use App\Models\CoreSettings\PaymentStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Exceptions;

use function PHPUnit\Framework\throwException;

class SaleItemService
{
    protected CashFlowService $cashFlowService;
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(CashFlowService $cashFlowService, AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->cashFlowService = $cashFlowService;
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }
  
   public function handleSaveSaleItem(array $validated, string $business_branch_id){
    $notificationService = app(NotificationService::class);
    $user = Auth::user();
      $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["unit_price"]);
      // create sale header
      $sale = Sale::create([
         'business_branch_id' => $business_branch_id,
          "total_amount" => $totalAmount,
          "customer_id" => $validated["customer_id"],
          'note' => $validated["note"] ?? null,
           "status" => "completed" 
           ]);

      // create sale items

    foreach ($validated['items'] as $item) {
      $product = BusinessBranchProduct::find($item['business_branch_product_id']);
      if($product->quantity < $item["quantity"]){
         throw new Exception("Products available are few to what you want to sale", 301);
      }
    //   Low stock check
    if ($product->quantity <= $product->reorder_level) {
    $notificationService->lowStockAlert(
        $user,
        $product->name ?? $product->id,
        $product->quantity,
        $product->reorder_level
    );
}
        SaleItem::create([
            'sale_id' => $sale->id,
            'business_branch_product_id' => $item['business_branch_product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['quantity'] * $item['unit_price'],
        ]);
        // decrement product stock
        BusinessBranchProduct::where('id', $item['business_branch_product_id'])
            ->decrement('quantity', $item['quantity']);
    }
    $method = PaymentStatus::find($validated["payment_status_id"])->value("method");
   //  to be removed
    SalePayment::create([
      "sale_id" => $sale->id,
      "method" => $method ?? "cash",
      "amount" => $totalAmount,
      "paymentStatus" => $validated["paymentStatus"],
    ]);
    $customer = Customer::with("user")->where("id", $validated["customer_id"])->first()->user;
    $customerName = $customer->firstname . " " . $customer->lastname;
    // ==================== CREATE CASH FLOW ====================
    $this->cashFlowService->createCashFlowForSale($sale, $totalAmount, $validated);
     // ==================== CREATE NOTIFICATION  ====================
    $notificationService->newSaleRecorded($user, number_format($totalAmount) , $customerName);

    return $sale->load(["saleItems", "salePayment"]);
   }



     // * Get sales analytics (Last 7 days by default)
  public function analytics(string $period = 'last_7_days')
    {

        $user = Auth::user();
        $branchId = $user->business_branch_id;

        $query = Sale::where('business_branch_id', $branchId)
                     ->where('status', 'completed');

        $days = $this->analyticsTrendHelper->getDaysFromPeriod($period);

        $query->where('created_at', '>=', Carbon::now()->subDays($days - 1));

        $sales = $query->get();

        $totalSales = $sales->sum('total_amount');
        $totalTransactions = $sales->count();
        $avgSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        // Group by date
        $salesTrend = $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->created_at)->format('M d');
        })->map(function ($group) {
            return [
                'date'   => $group->first()->created_at->format('M d'),
                'amount' => $group->sum('total_amount'),
                'count'  => $group->count(),
            ];
        })->values();

        // Fill missing dates with 0
        // $salesTrend = $this->fillMissingDates($salesTrend, $days);
        $salesTrend = $this->analyticsTrendHelper->fillMissingDates($salesTrend, $days);

        return [
            'total_sales'        => round($totalSales, 2),
            'avg_sale'           => round($avgSale, 2),
            'total_transactions' => $totalTransactions,
            'sales_trend'        => $salesTrend,
            'period'             => $period,
        ];
    }

   
}