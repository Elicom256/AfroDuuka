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
use Illuminate\Support\Collection;
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

                // Evaluated against the quantity this sale leaves behind, not the one it
                // starts with. The decrement happens further down, so reading
                // $product->quantity here reported stock that was about to change: a
                // product at 11 with a reorder level of 10, selling 3, lands on 8 and
                // this branch stayed silent because 11 > 10. PosService::checkout
                // decrements first and then checks, which is why it did not have this.
                $remaining = (int) $product->quantity - (int) $requestedQuantities->get((int) $product->id);

                if ($remaining <= (int) $product->reorder_level) {
                    $notificationService->lowStockAlert(
                        $user,
                        $product->name ?? $product->id,
                        $remaining,
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
        $window = $this->analyticsTrendHelper->resolvePeriodWindow($period);
        $currentStart = $window['start'];
        $currentEnd = $window['end'];
        $days = $window['days'];

        $sales = Sale::where('status', 'completed')
            ->where('created_at', '>=', $currentStart)
            ->where('created_at', '<=', $currentEnd)
            ->with('saleItems')
            ->get();

        // Revenue is dated by when the return was processed, so a period that has
        // already been reported never moves because of a later return.
        $returns = $this->returnsProcessedBetween($currentStart, $currentEnd);

        $grossSales = $sales->sum('total_amount');
        $totalSales = $grossSales - $returns['revenue'];
        $totalTransactions = $sales->count();
        $avgSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        // Units are different from money: only returns of sales made inside this
        // window may reduce this window's units sold.
        $returnedQuantity = $this->returnsOfSalesInPeriod($currentStart, $currentEnd)['quantity'];
        $itemsSold = $sales->sum(fn ($sale) => $sale->saleItems->sum('quantity')) - $returnedQuantity;

        $previousStart = match ($period) {
            'today' => Carbon::yesterday()->startOfDay(),
            'last_7_days' => Carbon::now()->subDays(13)->startOfDay(),
            'last_30_days' => Carbon::now()->subDays(59)->startOfDay(),
            'this_month' => Carbon::now()->subMonth()->startOfMonth(),
            'last_month' => Carbon::now()->subMonths(2)->startOfMonth(),
            default => Carbon::now()->subDays(13)->startOfDay(),
        };

        $previousSales = Sale::where('status', 'completed')
            ->where('created_at', '>=', $previousStart)
            ->where('created_at', '<', $currentStart)
            ->with('saleItems')
            ->get();

        $previousReturns = $this->returnsProcessedBetween($previousStart, $currentStart->copy()->subSecond());
        $previousReturnedQuantity = $this->returnsOfSalesInPeriod($previousStart, $currentStart->copy()->subSecond())['quantity'];

        $previousGrossSales = $previousSales->sum('total_amount');
        $previousTotalSales = $previousGrossSales - $previousReturns['revenue'];
        $previousTransactions = $previousSales->count();
        $previousAvg = $previousTransactions > 0 ? $previousTotalSales / $previousTransactions : 0;
        $previousItemsSold = $previousSales->sum(fn ($sale) => $sale->saleItems->sum('quantity')) - $previousReturnedQuantity;

        $salesTrend = $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->created_at)->format('M d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('M d'),
                // Gross by sale date, and net because returns processed the same
                // day are netted off here, so the points still sum to the totals.
                'amount' => $group->sum('total_amount'),
                'count' => $group->count(),
                'items' => $group->sum(fn ($sale) => $sale->saleItems->sum('quantity')),
            ];
        })->values();

        $salesTrend = $this->deductReturnsFromTrend($salesTrend, $currentStart, $currentEnd);
        $salesTrend = $window['calendar']
            ? $this->analyticsTrendHelper->fillMissingCalendarDates($salesTrend, $currentStart, $currentEnd)
            : $this->analyticsTrendHelper->fillMissingDates($salesTrend, $days);

        return [
            'gross_sales' => round($grossSales, 2),
            'sales_returns' => round($returns['revenue'], 2),
            'total_sales' => round($totalSales, 2),
            'returned_quantity' => $returns['quantity'],
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
     * Nets returns processed on each day off that day's gross trend point.
     *
     * The trend is what the chart draws, so it has to reconcile with the totals
     * above it. Deductions are bucketed by return date, matching the totals, which
     * means a same-day return lands on the same point as the sale it reverses.
     *
     * Units use the same-period restriction as the items_sold total, so a late
     * return cannot remove units from a day that never sold them.
     */
    protected function deductReturnsFromTrend($trend, $windowStart, $windowEnd)
    {
        $end = Carbon::parse($windowEnd)->endOfDay();

        $revenueLabels = $this->completedReturnLinesQuery()
            ->where('sale_returns.created_at', '>=', $windowStart)
            ->where('sale_returns.created_at', '<=', $end)
            ->selectRaw('sale_returns.created_at as processed_at, SUM(sale_return_items.subtotal) as revenue')
            ->groupBy('sale_returns.created_at')
            ->get()
            ->mapWithKeys(fn ($row) => [
                Carbon::parse($row->processed_at)->format('M d') => (float) $row->revenue,
            ]);

        $quantityLabels = $this->completedReturnLinesQuery()
            ->where('sale_returns.created_at', '>=', $windowStart)
            ->where('sale_returns.created_at', '<=', $end)
            ->whereIn('sale_return_items.sale_item_id', $this->saleItemIdsSoldBetween($windowStart, $end))
            ->selectRaw('sale_returns.created_at as processed_at, SUM(sale_return_items.quantity) as quantity')
            ->groupBy('sale_returns.created_at')
            ->get()
            ->mapWithKeys(fn ($row) => [
                Carbon::parse($row->processed_at)->format('M d') => (int) $row->quantity,
            ]);

        return $trend->map(function ($point) use ($revenueLabels, $quantityLabels) {
            $label = $point['date'];

            return [
                'date' => $label,
                'amount' => $point['amount'] - ($revenueLabels[$label] ?? 0),
                'count' => $point['count'],
                'items' => $point['items'] - ($quantityLabels[$label] ?? 0),
            ];
        })->values();
    }

    /**
     * Returned revenue and quantity per sale, keyed by sale id.
     *
     * This is an integrity view, not a reporting one. It answers "how much of this
     * sale has come back", which is what over-return checks need, and it deliberately
     * ignores dates so it spans the sale's whole life.
     *
     * Do not use it to build a period figure. Revenue is dated by when the return was
     * processed, not by the sale it came from, so a closed period must never move
     * because of a later return. See returnsProcessedBetween() for reporting.
     *
     * Only completed returns count. draft and cancelled rows still exist in
     * sale_return_items, so filtering here is what keeps an unapproved return from
     * reading as a real one.
     *
     * @return Collection<int, array{revenue: float, quantity: int}>
     */
    protected function returnsBySale($saleIds)
    {
        $saleIds = $saleIds->filter()->unique()->values();

        if ($saleIds->isEmpty()) {
            return collect();
        }

        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->where('sale_returns.status', 'completed')
            ->whereIn('sale_items.sale_id', $saleIds->all())
            ->groupBy('sale_items.sale_id')
            ->selectRaw(
                'sale_items.sale_id as sale_id,'
                .' SUM(sale_return_items.quantity) as quantity,'
                .' SUM(sale_return_items.quantity * sale_items.unit_price) as revenue'
            )
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->sale_id => [
                    'revenue' => (float) $row->revenue,
                    'quantity' => (int) $row->quantity,
                ],
            ]);
    }

    /**
     * Returned revenue and quantity for returns PROCESSED inside a window.
     *
     * A refund is a contra-revenue in the period it is paid out, regardless of
     * which sale it came from. So a return processed today against a sale from
     * last month reduces today's figures and leaves last month's alone: a period
     * that has already been reported must stay exactly as it was reported.
     * This is also how the cashflow side already dates refunds (transaction_date
     * at processing time), so the two agree by construction.
     *
     * @return array{revenue: float, quantity: int}
     */
    protected function returnsProcessedBetween($start, $end): array
    {
        $line = $this->completedReturnLinesQuery()
            ->where('sale_returns.created_at', '>=', $start)
            ->where('sale_returns.created_at', '<=', $end);

        return [
            'revenue' => (float) (clone $line)->sum('sale_return_items.subtotal'),
            'quantity' => (int) (clone $line)->sum('sale_return_items.quantity'),
        ];
    }

    /**
     * Quantity returned by returns processed in the window whose underlying sale
     * was ALSO sold in the window.
     *
     * Items sold counts units this business actually moved in the period, so it
     * may only be reduced by returns of sales from that same period. Without this
     * restriction, a late return would quietly shrink a period's unit count by
     * units it never sold. The revenue side deliberately has no such restriction.
     *
     * @return array{revenue: float, quantity: int}
     */
    protected function returnsOfSalesInPeriod($start, $end): array
    {
        $line = $this->completedReturnLinesQuery()
            ->where('sale_returns.created_at', '>=', $start)
            ->where('sale_returns.created_at', '<=', $end)
            ->whereIn('sale_return_items.sale_item_id', $this->saleItemIdsSoldBetween($start, $end));

        return [
            'revenue' => (float) (clone $line)->sum('sale_return_items.subtotal'),
            'quantity' => (int) (clone $line)->sum('sale_return_items.quantity'),
        ];
    }

    /**
     * Completed return lines, joined from the tenant-carrying table.
     *
     * Two things matter here. sale_return_items has no business_id column, so the
     * BaseModel tenant scope no-ops on it and building this query from it would
     * read across every tenant; joining from sale_returns, which does carry the
     * column, keeps the scope applied. And sale_returns is deliberately the only
     * tenant table in the join: sales also carries business_id, and the scope
     * emits an unqualified `where business_id = ?`, so joining both would make
     * that reference ambiguous (42702). Where a sale-side restriction is needed
     * it goes through a subquery, which scopes independently.
     */
    protected function completedReturnLinesQuery()
    {
        return SaleReturn::query()
            ->join('sale_return_items', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->where('sale_returns.status', 'completed');
    }

    /**
     * Ids of sale items belonging to completed sales made inside the window.
     *
     * Built from the Sale model so the tenant scope applies to the subquery, and
     * kept out of the outer joins so the outer query has exactly one
     * business_id-bearing table.
     */
    protected function saleItemIdsSoldBetween($start, $end)
    {
        return SaleItem::query()
            ->select('sale_items.id')
            ->whereIn('sale_items.sale_id', Sale::query()
                ->select('sales.id')
                ->where('status', 'completed')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end));
    }

    public function returnAnalytics(string $period = 'last_7_days')
    {
        $window = $this->analyticsTrendHelper->resolvePeriodWindow($period);
        $currentStart = $window['start'];
        $currentEnd = $window['end'];

        $sales = Sale::where('status', 'completed')
            ->where('created_at', '>=', $currentStart)
            ->where('created_at', '<=', $currentEnd)
            ->with('saleItems')
            ->get();

        $grossSales = $sales->sum('total_amount');
        $totalSoldQuantity = $sales->sum(fn ($sale) => $sale->saleItems->sum('quantity'));

        // Dated by processing time and limited to completed returns, so this
        // window can report returns made in it even when the sale they came from
        // sits outside the window, and can never include an unapproved one.
        // Scoped from SaleReturn because sale_return_items carries no tenant column.
        $saleReturns = SaleReturn::query()
            ->where('sale_returns.status', 'completed')
            ->where('sale_returns.created_at', '>=', $currentStart)
            ->where('sale_returns.created_at', '<=', $currentEnd)
            ->with('saleReturnItems')
            ->get();

        $returnedRevenue = $saleReturns->sum(fn ($return) => $return->saleReturnItems->sum('subtotal'));
        $returnedQuantity = $saleReturns->sum(fn ($return) => $return->saleReturnItems->sum('quantity'));

        // Net is signed on purpose. A window that refunded against earlier sales can
        // legitimately go negative, and clamping it would hide real losses.
        $netSales = $grossSales - $returnedRevenue;

        // Rate is share of the window's own gross sales. With no sales in the window
        // the ratio is undefined rather than 0, so report null instead of claiming
        // nothing was returned.
        $returnRate = $grossSales > 0
            ? round(($returnedRevenue / $grossSales) * 100, 2)
            : null;

        $returnsTrend = $saleReturns->groupBy(fn ($return) => $return->created_at->format('M d'))
            ->map(function ($group) {
                return [
                    'date' => $group->first()->created_at->format('M d'),
                    'amount' => $group->sum(fn ($return) => $return->saleReturnItems->sum('subtotal')),
                    'quantity' => $group->sum(fn ($return) => $return->saleReturnItems->sum('quantity')),
                ];
            })->values();

        $returnsTrend = $window['calendar']
            ? $this->analyticsTrendHelper->fillMissingCalendarDates($returnsTrend, $currentStart, $currentEnd)
            : $this->analyticsTrendHelper->fillMissingDates($returnsTrend, $window['days']);

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
