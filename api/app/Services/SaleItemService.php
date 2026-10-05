<?php

namespace App\Services;

use App\Models\CoreSettings\PaymentMethod;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use App\Support\Tenant\EffectiveBranchScope;
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
        if (empty($validated['items']) || count($validated['items']) < 1) {
            throw new Exception('A sale must have at least one item.', 422);
        }

        return DB::transaction(function () use ($validated, $business_branch_id) {
            $notificationService = app(NotificationService::class);
            $user = Auth::user();
            $taxService = app(TaxService::class);

            $branchId = $validated['business_branch_id'] ?? $business_branch_id ?? $user?->business_branch_id;
            $resolved = $user ? EffectiveBranchScope::branchesFor($user) : null;
            if ($branchId && $resolved !== null) {
                [, $branchIds] = $resolved;
                if (! in_array($branchId, $branchIds, true)) {
                    throw new Exception('Branch is not within your allowed scope', 403);
                }
            }

            if (! $branchId) {
                throw new Exception('A valid business branch is required to complete this sale.', 422);
            }

            $productIds = collect($validated['items'])->pluck('product_id')->filter()->unique()->values()->all();
            $requestedQuantities = collect($validated['items'])
                ->groupBy(fn ($item) => (int) $item['product_id'])
                ->map(fn ($items) => $items->sum(fn ($item) => (int) $item['quantity']));
            $products = Product::with('taxCategory.taxRates')
                ->whereIn('id', $productIds)
                ->where('business_branch_id', $branchId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (count($products) !== count($productIds)) {
                throw new Exception('One or more selected products do not belong to the selected branch.', 422);
            }

            $lineTaxes = [];
            $totalSubtotal = 0;
            $totalTaxAmount = 0;

            foreach (array_values($validated['items']) as $index => $item) {
                $product = $products->get($item['product_id']);
                if (! $product) {
                    throw new Exception('Product not found', 404);
                }
                if ($product->quantity < $requestedQuantities->get((int) $product->id)) {
                    throw new Exception('Products available are few to what you want to sale', 301);
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
                    (int) $item['quantity'],
                    (float) ($item['discount'] ?? 0)
                );
                $lineTaxes[$index] = $tax;
                $totalSubtotal += $tax['taxable_amount'];
                $totalTaxAmount += $tax['tax_amount'];
            }

            $totalAmount = round($totalSubtotal + $totalTaxAmount, 2);

            $sale = Sale::create([
                'business_branch_id' => $branchId,
                'subtotal' => round($totalSubtotal, 2),
                'tax_amount' => round($totalTaxAmount, 2),
                'total_amount' => $totalAmount,
                'customer_id' => $validated['customer_id'],
                'note' => $validated['note'] ?? null,
                'status' => 'completed',
            ]);

            foreach (array_values($validated['items']) as $index => $item) {
                $product = $products->get($item['product_id']);
                $tax = $lineTaxes[$index];

                $lineDiscount = ((float) ($item['discount'] ?? 0)) * $item['quantity'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax_rate' => $tax['rate'],
                    'is_tax_inclusive' => $tax['is_tax_inclusive'],
                    'taxable_amount' => $tax['taxable_amount'],
                    'tax_amount' => $tax['tax_amount'],
                    'subtotal' => ($item['quantity'] * $item['unit_price']) - $lineDiscount,
                ]);
            }

            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $products->get($productId);
                $product->decrement('quantity', $quantity);
                $product->update(['last_sold_at' => now()]);

                StockMovement::updateOrCreate(
                    [
                        'movement_key' => md5(Sale::class.':'.$sale->id.':'.$product->id.':out:'.$quantity),
                    ],
                    [
                        'business_id' => $user->business_id,
                        'business_branch_id' => $branchId,
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $quantity,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => 'Non-POS sale',
                    ]
                );
            }

            $paymentMethod = PaymentMethod::find($validated['payment_status_id'] ?? null);
            if (! $paymentMethod) {
                throw new Exception('Selected payment method is invalid.', 422);
            }

            $method = $paymentMethod->method;
            SalePayment::create([
                'sale_id' => $sale->id,
                'method' => $method,
                'amount' => $totalAmount,
                'paymentStatus' => $validated['paymentStatus'],
            ]);

            $customer = isset($validated['customer_id']) ?
                        Customer::with('user')->where('id', $validated['customer_id'])->first()?->user : null;
            $customerName = $customer ? $customer->firstname.' '.$customer->lastname : 'unknown';
            $this->cashFlowService->createCashFlowForSale($sale, $totalAmount, $validated);
            $notificationService->newSaleRecorded($user, number_format($totalAmount), $customerName, $sale->id);

            $receiptService = app(ReceiptService::class);
            $paymentMethodName = $method ?? 'cash';
            $validated['payment_method'] = $paymentMethodName;
            $receiptService->createReceiptForSale($sale, $validated);

            return $sale->load(['saleItems', 'salePayments', 'receipt']);
        });
    }

    public function analytics(string $period = 'last_7_days')
    {
        $query = Sale::where('status', 'completed');

        $days = $this->analyticsTrendHelper->getDaysFromPeriod($period);
        $currentStart = $period === 'today' ? Carbon::today() : Carbon::now()->subDays($days - 1);
        $query->where('created_at', '>=', $currentStart);

        $sales = $query->with('saleItems')->get();

        // Returns never mutate sales/sale_items; they are recorded separately and
        // reconciled here so revenue and items sold read net of returns.
        $returns = $this->returnsForSales($sales->pluck('id'));
        $returnedRevenue = $returns['revenue'];
        $returnedQuantity = $returns['quantity'];

        $grossSales = $sales->sum('total_amount');
        $totalSales = $grossSales - $returnedRevenue;
        $totalTransactions = $sales->count();
        $avgSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        $itemsSold = $sales->sum(fn ($sale) => $sale->saleItems->sum('quantity')) - $returnedQuantity;

        $previousStart = match ($period) {
            'today' => Carbon::yesterday(),
            'last_7_days' => Carbon::now()->subDays(13),
            'last_30_days' => Carbon::now()->subDays(59),
            'this_month' => Carbon::now()->subMonth()->startOfMonth(),
            'last_month' => Carbon::now()->subMonths(2)->startOfMonth(),
            default => Carbon::now()->subDays(13),
        };

        $previousSales = Sale::where('status', 'completed')
            ->where('created_at', '>=', $previousStart)
            ->where('created_at', '<', $currentStart)
            ->with('saleItems')
            ->get();

        $previousReturns = $this->returnsForSales($previousSales->pluck('id'));
        $previousReturnedRevenue = $previousReturns['revenue'];
        $previousReturnedQuantity = $previousReturns['quantity'];

        $previousGrossSales = $previousSales->sum('total_amount');
        $previousTotalSales = $previousGrossSales - $previousReturnedRevenue;
        $previousTransactions = $previousSales->count();
        $previousAvg = $previousTransactions > 0 ? $previousTotalSales / $previousTransactions : 0;
        $previousItemsSold = $previousSales->sum(fn ($sale) => $sale->saleItems->sum('quantity')) - $previousReturnedQuantity;

        $salesTrend = $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->created_at)->format('M d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('M d'),
                'amount' => $group->sum('total_amount'),
                'count' => $group->count(),
                'items' => $group->sum(fn ($sale) => $sale->saleItems->sum('quantity')),
            ];
        })->values();

        $salesTrend = $this->analyticsTrendHelper->fillMissingDates($salesTrend, $days);

        return [
            'gross_sales' => round($grossSales, 2),
            'sales_returns' => round($returnedRevenue, 2),
            'total_sales' => round($totalSales, 2),
            'returned_quantity' => $returnedQuantity,
            'avg_sale' => round($avgSale, 2),
            'total_transactions' => $totalTransactions,
            'items_sold' => $itemsSold,
            'sales_trend' => $salesTrend,
            'period' => $period,
            'previous' => [
                'total_sales' => round($previousTotalSales, 2),
                'avg_sale' => round($previousAvg, 2),
                'total_transactions' => $previousTransactions,
                'items_sold' => $previousItemsSold,
            ],
            'lable' => 'sales',
        ];
    }

    /**
     * Total returned revenue and quantity across the given sales.
     *
     * A return is financially dated by when it was processed, but the quantity it
     * removes belongs to the original sale. Joining through sale_item_id keeps the
     * deduction attached to the sale that earned the revenue, so a return processed
     * today still reduces the sale it belongs to.
     *
     * @return array{revenue: float, quantity: int}
     */
    protected function returnsForSales($saleIds): array
    {
        $unitPrices = SaleItem::whereIn('sale_id', $saleIds->filter()->all())
            ->pluck('unit_price', 'id');

        if ($unitPrices->isEmpty()) {
            return ['revenue' => 0.0, 'quantity' => 0];
        }

        $quantities = SaleReturnItem::whereIn('sale_item_id', $unitPrices->keys())
            ->selectRaw('sale_item_id, SUM(quantity) as quantity')
            ->groupBy('sale_item_id')
            ->pluck('quantity', 'sale_item_id');

        $revenue = $quantities->sum(
            fn ($quantity, $saleItemId) => (float) $quantity * (float) ($unitPrices[$saleItemId] ?? 0)
        );

        return [
            'revenue' => (float) $revenue,
            'quantity' => (int) $quantities->sum(),
        ];
    }

    public function returnAnalytics(string $period = 'last_7_days')
    {
        $days = $this->analyticsTrendHelper->getDaysFromPeriod($period);
        $currentStart = $period === 'today' ? Carbon::today() : Carbon::now()->subDays($days - 1);

        $sales = Sale::where('status', 'completed')
            ->where('created_at', '>=', $currentStart)
            ->with('saleItems')
            ->get();

        $grossSales = $sales->sum('total_amount');
        $totalSoldQuantity = $sales->sum(fn ($sale) => $sale->saleItems->sum('quantity'));

        $saleReturnItems = SaleReturnItem::whereHas('saleReturn', function ($query) use ($currentStart) {
            $query->where('created_at', '>=', $currentStart);
        })->with('saleItem')->get();

        $returnedRevenue = $saleReturnItems->sum('subtotal');
        $returnedQuantity = $saleReturnItems->sum('quantity');

        $netSales = $grossSales - $returnedRevenue;
        $returnRate = $grossSales > 0 ? round(($returnedRevenue / $grossSales) * 100, 2) : 0;

        $returnsTrend = $saleReturnItems->groupBy(function ($item) {
            return Carbon::parse($item->saleReturn->created_at)->format('M d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->saleReturn->created_at->format('M d'),
                'amount' => $group->sum('subtotal'),
                'quantity' => $group->sum('quantity'),
            ];
        })->values();

        $returnsTrend = $this->analyticsTrendHelper->fillMissingDates($returnsTrend, $days);

        return [
            'gross_sales' => round($grossSales, 2),
            'sales_returns' => round($returnedRevenue, 2),
            'net_sales' => round($netSales, 2),
            'returned_quantity' => $returnedQuantity,
            'returned_revenue' => round($returnedRevenue, 2),
            'return_rate' => $returnRate,
            'total_sold_quantity' => $totalSoldQuantity,
            'returns_trend' => $returnsTrend,
            'period' => $period,
        ];
    }
}
