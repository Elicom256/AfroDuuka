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

   public function handleSaveSaleItem(array $validated, ?string $business_branch_id = null)
   {
        if (empty($validated["items"]) || count($validated["items"]) < 1) {
            throw new Exception("A sale must have at least one item.", 422);
        }

        return DB::transaction(function () use ($validated, $business_branch_id) {
            $notificationService = app(NotificationService::class);
            $user = Auth::user();
            $taxService = app(TaxService::class);

            $branchId = $validated['business_branch_id'] ?? $business_branch_id ?? $user?->business_branch_id;
            $resolved = $user ? \App\Support\Tenant\EffectiveBranchScope::branchesFor($user) : null;
            if ($branchId && $resolved !== null) {
                [, $branchIds] = $resolved;
                if (! in_array($branchId, $branchIds, true)) {
                    throw new Exception("Branch is not within your allowed scope", 403);
                }
            }
            $productIds = collect($validated["items"])->pluck('product_id')->unique()->values()->all();
            $products = Product::with('taxCategory.taxRates')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $lineTaxes = [];
            $totalSubtotal = 0;
            $totalTaxAmount = 0;

            foreach (array_values($validated['items']) as $index => $item) {
                $product = $products->get($item['product_id']);
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
                        $product->reorder_level,
                        $product->id
                    );
                }

                $tax = $taxService->calculateForProduct(
                    $product,
                    (float) $item['unit_price'],
                    (int) $item['quantity']
                );
                $lineTaxes[$index] = $tax;
                $totalSubtotal += $tax['taxable_amount'];
                $totalTaxAmount += $tax['tax_amount'];
            }

            $totalAmount = round($totalSubtotal + $totalTaxAmount, 2);

            $sale = Sale::create([
                'business_branch_id' => $branchId,
                'subtotal'           => round($totalSubtotal, 2),
                'tax_amount'         => round($totalTaxAmount, 2),
                "total_amount"       => $totalAmount,
                "customer_id"        => $validated["customer_id"],
                'note'               => $validated["note"] ?? null,
                "status"             => "completed"
            ]);

            foreach (array_values($validated['items']) as $index => $item) {
                $product = $products->get($item['product_id']);
                $tax = $lineTaxes[$index];

                SaleItem::create([
                    'sale_id'          => $sale->id,
                    'product_id'       => $item['product_id'],
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $item['unit_price'],
                    'tax_rate'         => $tax['rate'],
                    'is_tax_inclusive' => $tax['is_tax_inclusive'],
                    'taxable_amount'   => $tax['taxable_amount'],
                    'tax_amount'       => $tax['tax_amount'],
                    'subtotal'         => $item['quantity'] * $item['unit_price'],
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
            $notificationService->newSaleRecorded($user, number_format($totalAmount), $customerName, $sale->id);

            $receiptService = app(ReceiptService::class);
            $paymentMethodName = $method ?? 'cash';
            $validated['payment_method'] = $paymentMethodName;
            $receiptService->createReceiptForSale($sale, $validated);

            return $sale->load(["saleItems", "salePayment", "receipt"]);
        });
   }

    public function analytics(string $period = 'last_7_days')
    {
        $query = Sale::where('status', 'completed');

        $days = $this->analyticsTrendHelper->getDaysFromPeriod($period);
        $currentStart = $period === 'today' ? Carbon::today() : Carbon::now()->subDays($days - 1);
        $query->where('created_at', '>=', $currentStart);

        $sales = $query->with('saleItems')->get();

        $totalSales = $sales->sum('total_amount');
        $totalTransactions = $sales->count();
        $avgSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        $itemsSold = $sales->sum(fn ($sale) => $sale->saleItems->sum('quantity'));

        $previousStart = match ($period) {
            'today'       => Carbon::yesterday(),
            'last_7_days'  => Carbon::now()->subDays(13),
            'last_30_days' => Carbon::now()->subDays(59),
            'this_month'  => Carbon::now()->subMonth()->startOfMonth(),
            'last_month'  => Carbon::now()->subMonths(2)->startOfMonth(),
            default       => Carbon::now()->subDays(13),
        };

        $previousSales = Sale::where('status', 'completed')
            ->where('created_at', '>=', $previousStart)
            ->where('created_at', '<', $currentStart)
            ->with('saleItems')
            ->get();

        $previousTotalSales = $previousSales->sum('total_amount');
        $previousTransactions = $previousSales->count();
        $previousAvg = $previousTransactions > 0 ? $previousTotalSales / $previousTransactions : 0;
        $previousItemsSold = $previousSales->sum(fn ($sale) => $sale->saleItems->sum('quantity'));

        $salesTrend = $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->created_at)->format('M d');
        })->map(function ($group) {
            return [
                'date'   => $group->first()->created_at->format('M d'),
                'amount' => $group->sum('total_amount'),
                'count'  => $group->count(),
                'items'  => $group->sum(fn ($sale) => $sale->saleItems->sum('quantity')),
            ];
        })->values();

        $salesTrend = $this->analyticsTrendHelper->fillMissingDates($salesTrend, $days);

        return [
            'total_sales'        => round($totalSales, 2),
            'avg_sale'           => round($avgSale, 2),
            'total_transactions' => $totalTransactions,
            'items_sold'         => $itemsSold,
            'sales_trend'        => $salesTrend,
            'period'             => $period,
            'previous'           => [
                'total_sales'        => round($previousTotalSales, 2),
                'avg_sale'           => round($previousAvg, 2),
                'total_transactions' => $previousTransactions,
                'items_sold'         => $previousItemsSold,
            ],
            "lable"              => "sales"
        ];
    }
}
