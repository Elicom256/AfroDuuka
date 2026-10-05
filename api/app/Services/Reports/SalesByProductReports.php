<?php

namespace App\Services\Reports;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\AnalyticsTrendHelper;
use App\Support\Tenant\EffectiveBranchScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesByProductReports
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    /**
     * Best selling products for a period, net of returns.
     *
     * This is the merchandising question, "did these products actually sell?", so it
     * deliberately does NOT use the processing-date basis that the financial figures
     * use. A return is deducted from the sale it came from whenever that return was
     * processed, which means a late return can change an older period's row here. That
     * is the intended difference between the two views: finance reports a refund in
     * the period it was paid out, merchandising reports the sale it unwound. Do not
     * "fix" this to match FinanceService, it would answer a different question.
     *
     * Only completed returns deduct. Returns against sales outside the window are
     * ignored entirely, since their sale is not one of the rows being reported.
     *
     * @param  array<string, mixed>  $filters
     * @return array{filter: string, top_products: Collection<int, array<string, mixed>>}
     */
    public function salesByProduct(array $filters, User $user): array
    {
        $filter = $filters['filter'] ?? 'this_month';
        $dates = $this->analyticsTrendHelper->getPeriodDates($filter);
        $startDate = Carbon::parse($dates['start'])->startOfDay();
        $endDate = Carbon::parse($dates['end'])->endOfDay();

        $branchIds = EffectiveBranchScope::branchesFor($user)[1] ?? null;

        $gross = SaleItem::query()
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
            ->get()
            ->keyBy('product_id');

        $returned = $this->returnsByProduct($startDate, $endDate, $branchIds);

        // Ranking happens on the net figures, otherwise a product that sold well and
        // was returned in full would still occupy a top ten slot it no longer earns.
        $rows = $gross->map(function ($row) use ($returned) {
            $return = $returned[(int) $row->product_id] ?? ['quantity' => 0, 'revenue' => 0.0];

            return [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'quantity_sold' => (int) $row->quantity_sold - $return['quantity'],
                'total_revenue' => round((float) $row->total_revenue - $return['revenue'], 2),
                'quantity_sold_gross' => (int) $row->quantity_sold,
                'returned_quantity' => $return['quantity'],
                'returned_revenue' => round($return['revenue'], 2),
            ];
        })
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values();

        return [
            'filter' => $filter,
            'top_products' => $rows,
        ];
    }

    /**
     * Returned quantity and revenue per product, for sales made inside the window.
     *
     * Built from SaleReturn because sale_return_items carries no business_id column,
     * so scoping from it would read across every tenant. sale_returns is the only
     * tenant-bearing table in the join: sales also carries business_id and the tenant
     * scope emits an unqualified `where business_id = ?`, so joining it directly would
     * make that reference ambiguous. The sale restriction therefore goes through a
     * subquery, which scopes on its own.
     *
     * @param  int[]|null  $branchIds
     * @return array<int, array{quantity: int, revenue: float}>
     */
    private function returnsByProduct($startDate, $endDate, ?array $branchIds): array
    {
        return SaleReturn::query()
            ->join('sale_return_items', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->where('sale_returns.status', 'completed')
            // Same sale population as the gross query, so a return can never deduct
            // from a product whose sale was not counted in the first place.
            ->whereIn('sale_items.sale_id', Sale::query()
                ->select('sales.id')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($branchIds !== null, function ($query) use ($branchIds) {
                    $query->whereIn('business_branch_id', $branchIds);
                }))
            ->groupBy('sale_items.product_id')
            ->selectRaw(
                'sale_items.product_id as product_id,'
                .' SUM(sale_return_items.quantity) as quantity,'
                .' SUM(sale_return_items.subtotal) as revenue'
            )
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->product_id => [
                    'quantity' => (int) $row->quantity,
                    'revenue' => (float) $row->revenue,
                ],
            ])
            ->all();
    }
}
