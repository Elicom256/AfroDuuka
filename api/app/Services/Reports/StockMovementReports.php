<?php

namespace App\Services\Reports;

use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\AnalyticsTrendHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockMovementReports
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    public function stockMovements(array $filters, User $user): array
    {
        $filter = $filters['filter'] ?? 'last_7_days';
        $dates = $this->analyticsTrendHelper->getPeriodDates($filter);
        $startDate = Carbon::parse($dates['start'])->startOfDay();
        $endDate = Carbon::parse($dates['end'])->endOfDay();

        $purchaseTrend = PurchaseItem::query()
            ->select([
                DB::raw('DATE(purchases.created_at) as date'),
                DB::raw('SUM(purchase_items.quantity) as stock_in'),
            ])
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.business_branch_id', $user->business_branch_id)
            ->whereBetween('purchases.created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->date => (int) $item->stock_in]);

        $salesTrend = SaleItem::query()
            ->select([
                DB::raw('DATE(sales.created_at) as date'),
                DB::raw('SUM(sale_items.quantity) as stock_out'),
            ])
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_branch_id', $user->business_branch_id)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->date => (int) $item->stock_out]);

        $trend = [];
        $currentDate = $startDate->copy();
        $totalStockIn = 0;
        $totalStockOut = 0;

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->toDateString();
            $stockIn = $purchaseTrend[$dateKey] ?? 0;
            $stockOut = $salesTrend[$dateKey] ?? 0;
            $netMovement = $stockIn - $stockOut;

            $trend[] = [
                'date' => $dateKey,
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'net_movement' => $netMovement,
            ];

            $totalStockIn += $stockIn;
            $totalStockOut += $stockOut;
            $currentDate->addDay();
        }

        return [
            'filter' => $filter,
            'summary' => [
                'total_stock_in' => $totalStockIn,
                'total_stock_out' => $totalStockOut,
                'net_movement' => $totalStockIn - $totalStockOut,
            ],
            'trend' => $trend,
        ];
    }
}
