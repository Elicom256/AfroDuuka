<?php

namespace App\Services\Reports;

use App\Models\SaleItem;
use App\Models\User;
use App\Services\AnalyticsTrendHelper;
use App\Support\Tenant\EffectiveBranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesByProductReports
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    public function salesByProduct(array $filters, User $user): array
    {
        $filter = $filters['filter'] ?? 'this_month';
        $dates = $this->analyticsTrendHelper->getPeriodDates($filter);
        $startDate = Carbon::parse($dates['start'])->startOfDay();
        $endDate = Carbon::parse($dates['end'])->endOfDay();

        $branchIds = EffectiveBranchScope::branchesFor($user)[1] ?? null;

        $products = SaleItem::query()
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue'),
            ])
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->when($branchIds !== null, function ($query) use ($branchIds) {
                $query->whereIn('sales.business_branch_id', $branchIds);
            })
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return [
            'filter' => $filter,
            'top_products' => $products,
        ];
    }
}
