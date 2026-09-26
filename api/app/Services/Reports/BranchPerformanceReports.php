<?php

namespace App\Services\Reports;

use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\User;
use App\Services\AnalyticsTrendHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BranchPerformanceReports
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    public function branchPerformance(string $filter, User $user, ?int $branchId = null): array
    {
        $dates = $this->analyticsTrendHelper->getPeriodDates($filter);
        $startDate = Carbon::parse($dates['start'])->toDateString();
        $endDate = Carbon::parse($dates['end'])->toDateString();

        $query = CashFlow::query()
            ->select([
                'cash_flows.business_branch_id as branch_id',
                DB::raw("SUM(CASE WHEN cash_flows.type = 'sale' OR cash_flows.category = 'product_sales' THEN cash_flows.amount ELSE 0 END) as total_revenue"),
                DB::raw("SUM(CASE WHEN cash_flows.type IN ('purchase','expense') THEN cash_flows.amount ELSE 0 END) as total_expenses"),
                DB::raw("COUNT(CASE WHEN cash_flows.type = 'sale' OR cash_flows.category = 'product_sales' THEN 1 END) as transaction_count"),
            ])
            ->where('cash_flows.status', 'completed')
            ->whereBetween('cash_flows.transaction_date', [$startDate, $endDate])
            // No join to business_branches. Both tables carry business_id, and the
            // tenant scope emits an unqualified `where business_id = ?`, so the join
            // made that reference ambiguous and this endpoint answered 42702 on every
            // request. Names are resolved in a second query instead.
            ->groupBy('cash_flows.business_branch_id')
            ->orderByDesc('total_revenue');

        if ($branchId !== null) {
            $query->where('cash_flows.business_branch_id', $branchId);
        }

        $reportRows = $query->get();

        // Scoped to the business explicitly: this query runs outside the branch scope
        // that produced the ids above, and a name is still tenant data.
        $names = $reportRows->isEmpty()
            ? []
            : BusinessBranch::where('business_id', $user->business_id)
                ->whereIn('id', $reportRows->pluck('branch_id')->all())
                ->pluck('name', 'id')
                ->all();

        $companyTotals = CashFlow::query()
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select([
                DB::raw("SUM(CASE WHEN type = 'sale' OR category = 'product_sales' THEN amount ELSE 0 END) as total_revenue"),
                DB::raw("SUM(CASE WHEN type IN ('purchase','expense') THEN amount ELSE 0 END) as total_expenses"),
            ])
            ->first();

        $totalCompanyRevenue = (float) ($companyTotals->total_revenue ?? 0);
        $totalCompanyExpenses = (float) ($companyTotals->total_expenses ?? 0);
        $totalCompanyProfit = $totalCompanyRevenue - $totalCompanyExpenses;

        $branches = $reportRows->map(function ($row) use ($totalCompanyRevenue, $names) {
            $totalRevenue = (float) $row->total_revenue;
            $totalExpenses = (float) $row->total_expenses;
            $transactionCount = (int) $row->transaction_count;
            $averageRevenuePerTransaction = $transactionCount > 0
                ? round($totalRevenue / $transactionCount, 2)
                : 0;
            $netProfit = round($totalRevenue - $totalExpenses, 2);
            $contribution = $totalCompanyRevenue > 0
                ? round(($totalRevenue / $totalCompanyRevenue) * 100, 2)
                : 0;

            return [
                'branch_id' => $row->branch_id,
                'branch_name' => $names[$row->branch_id] ?? 'Unknown branch',
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_profit' => $netProfit,
                'transaction_count' => $transactionCount,
                'average_revenue_per_transaction' => $averageRevenuePerTransaction,
                'revenue_contribution_percentage' => $contribution,
            ];
        })->toArray();

        return [
            'summary' => [
                'best_performing_branch' => $branches[0] ?? null,
                'worst_performing_branch' => count($branches) ? $branches[count($branches) - 1] : null,
                'total_company_revenue' => round($totalCompanyRevenue, 2),
                'total_company_expenses' => round($totalCompanyExpenses, 2),
                'total_company_profit' => round($totalCompanyProfit, 2),
            ],
            'branches' => $branches,
        ];
    }
}
