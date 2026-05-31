<?php

namespace App\Services\Reports;

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

    public function branchPerformance(string $filter, User $user): array
    {
        $dates = $this->analyticsTrendHelper->getPeriodDates($filter);
        $startDate = Carbon::parse($dates['start'])->toDateString();
        $endDate = Carbon::parse($dates['end'])->toDateString();

        $reportRows = CashFlow::query()
            ->select([
                'cash_flows.business_branch_id as branch_id',
                'business_branches.name as branch_name',
                DB::raw("SUM(CASE WHEN cash_flows.type = 'sale' OR cash_flows.category = 'product_sales' THEN cash_flows.amount ELSE 0 END) as total_revenue"),
                DB::raw("SUM(CASE WHEN cash_flows.type IN ('purchase','expense') THEN cash_flows.amount ELSE 0 END) as total_expenses"),
                DB::raw("COUNT(CASE WHEN cash_flows.type = 'sale' OR cash_flows.category = 'product_sales' THEN 1 END) as transaction_count"),
            ])
            ->join('business_branches', 'business_branches.id', '=', 'cash_flows.business_branch_id')
            ->where('cash_flows.business_id', $user->business_id)
            ->where('cash_flows.status', 'completed')
            ->whereBetween('cash_flows.transaction_date', [$startDate, $endDate])
            ->groupBy('cash_flows.business_branch_id', 'business_branches.name')
            ->orderByDesc('total_revenue')
            ->get();

        $companyTotals = CashFlow::query()
            ->where('business_id', $user->business_id)
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

        $branches = $reportRows->map(function ($row) use ($totalCompanyRevenue) {
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
                'branch_name' => $row->branch_name,
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
