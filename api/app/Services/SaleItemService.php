<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleItemService
{
    protected CashFlowService $cashFlowService;
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(CashFlowService $cashFlowService, AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->cashFlowService = $cashFlowService;
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

   public function handleSaveSaleItem(array $validated, string $business_branch_id)
   {
        if (empty($validated["items"]) || count($validated["items"]) < 1) {
            throw new Exception("A sale must have at least one item.", 422);
        }

        return DB::transaction(function () use ($validated, $business_branch_id) {
            $notificationService = app(NotificationService::class);
            $user = Auth::user();

            $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["unit_price"]);
            $sale = Sale::create([
                'business_branch_id' => $business_branch_id,
                "total_amount" => $totalAmount,
                "customer_id" => $validated["customer_id"],
                'note' => $validated["note"] ?? null,
                "status" => "completed"
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new Exception("Product not found", 404);
                }
                if ($product->quantity < $item["quantity"]) {
                    throw new Exception("Products available are few to what you want to sale", 301);
                }
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
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
                $product->decrement("quantity", $item['quantity']);
                $product->update(['last_sold_at' => now()]);
            }

            $method = PaymentMethod::find($validated["payment_status_id"])->value("method");
            SalePayment::create([
                "sale_id" => $sale->id,
                "method" => $method ?? "cash",
                "amount" => $totalAmount,
                "paymentStatus" => $validated["paymentStatus"],
            ]);

            $customer = isset($validated["customer_id"]) ?
                        Customer::with("user")->where("id", $validated["customer_id"])->first()?->user : null;
            $customerName = $customer ? $customer->firstname . " " . $customer->lastname : "unknown";
            $this->cashFlowService->createCashFlowForSale($sale, $totalAmount, $validated);
            $notificationService->newSaleRecorded($user, number_format($totalAmount), $customerName);

            $receiptService = app(ReceiptService::class);
            $paymentMethodName = $method ?? 'cash';
            $validated['payment_method'] = $paymentMethodName;
            $receipt = $receiptService->createReceiptForSale($sale, $validated);

            return $sale->load(["saleItems", "salePayment", "receipt"]);
        });
   }

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

        $salesTrend = $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->created_at)->format('M d');
        })->map(function ($group) {
            return [
                'date'   => $group->first()->created_at->format('M d'),
                'amount' => $group->sum('total_amount'),
                'count'  => $group->count(),
            ];
        })->values();

        $salesTrend = $this->analyticsTrendHelper->fillMissingDates($salesTrend, $days);

        return [
            'total_sales'        => round($totalSales, 2),
            'avg_sale'           => round($avgSale, 2),
            'total_transactions' => $totalTransactions,
            'sales_trend'        => $salesTrend,
            'period'             => $period,
            "lable"              => "sales"
        ];
    }
}
