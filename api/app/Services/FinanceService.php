<?php

namespace App\Services;

use App\Models\CashFlow;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    public function dashboard(?string $branchId = null): array
    {
        $query = CashFlow::query();
        if ($branchId) {
            $query->where('business_branch_id', $branchId);
        }

        $grossRevenue = (clone $query)->whereIn('type', ['sale', 'payment_in'])->sum('amount');
        $totalRefunds = (clone $query)->where('type', 'refund')->sum('amount');
        $totalRevenue = $grossRevenue - $totalRefunds;
        $totalExpenses = (clone $query)->whereIn('type', ['purchase', 'expense', 'payment_out'])->sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;

        $latest = (clone $query)->whereNotNull('running_balance')->orderBy('created_at', 'desc')->first();
        $cashBalance = $latest ? $latest->running_balance : 0;

        $recentTransactions = (clone $query)
            ->with(['branch', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'gross_revenue' => $grossRevenue,
            'total_refunds' => $totalRefunds,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'cash_balance' => $cashBalance,
            'recent_transactions' => $recentTransactions,
        ];
    }

    public function runningBalance(?string $branchId = null): void
    {
        $query = CashFlow::query();
        if ($branchId) {
            $query->where('business_branch_id', $branchId);
        }

        $records = $query->orderBy('created_at', 'asc')->get();
        $balance = 0;

        foreach ($records as $record) {
            if ($record->is_inflow) {
                $balance += $record->amount;
            } else {
                $balance -= $record->amount;
            }
            $record->update(['running_balance' => $balance]);
        }
    }

    public function revenueReport(?string $branchId, string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        $query = CashFlow::whereIn('type', ['sale', 'payment_in', 'refund'])
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('business_branch_id', $branchId);
        }

        $dateFormat = match ($groupBy) {
            'week' => 'IYYY-IW',
            'month' => 'YYYY-MM',
            default => 'YYYY-MM-DD',
        };

        $records = $query->select(
            DB::raw("TO_CHAR(transaction_date, '$dateFormat') as date"),
            DB::raw("SUM(CASE WHEN type IN ('sale', 'payment_in') THEN amount ELSE 0 END) as gross_revenue"),
            DB::raw("SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as refunds"),
            DB::raw("SUM(CASE WHEN type IN ('sale', 'payment_in') THEN amount ELSE -amount END) as revenue"),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $records->toArray();
    }

    public function expenseReport(?string $branchId, string $startDate, string $endDate): array
    {
        $query = CashFlow::whereIn('type', ['purchase', 'expense', 'payment_out'])
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('business_branch_id', $branchId);
        }

        $records = $query->select(
            'category',
            DB::raw('SUM(amount) as amount'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('category')
            ->orderBy('amount', 'desc')
            ->get();

        return $records->toArray();
    }

    public function incomeSummary(?string $branchId, string $year): array
    {
        $query = CashFlow::whereYear('transaction_date', $year);

        if ($branchId) {
            $query->where('business_branch_id', $branchId);
        }

        $revenue = (clone $query)->whereIn('type', ['sale', 'payment_in'])
            ->select(
                DB::raw('EXTRACT(MONTH FROM transaction_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $refunds = (clone $query)->where('type', 'refund')
            ->select(
                DB::raw('EXTRACT(MONTH FROM transaction_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $expenses = (clone $query)->whereIn('type', ['purchase', 'expense', 'payment_out'])
            ->select(
                DB::raw('EXTRACT(MONTH FROM transaction_date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $summary = [];
        for ($m = 1; $m <= 12; $m++) {
            $gross = (float) ($revenue->get($m)->total ?? 0);
            $ref = (float) ($refunds->get($m)->total ?? 0);
            $rev = $gross - $ref;
            $exp = (float) ($expenses->get($m)->total ?? 0);
            $net = $rev - $exp;
            $summary[] = [
                'month' => $monthNames[$m],
                'gross_revenue' => $gross,
                'refunds' => $ref,
                'revenue' => $rev,
                'expenses' => $exp,
                'net_profit' => $net,
                'profit_margin' => $rev > 0 ? round(($net / $rev) * 100, 1) : 0,
            ];
        }

        return $summary;
    }

    public function branchStatement(string $branchId): array
    {
        $records = CashFlow::where('business_branch_id', $branchId)
            ->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $grossRevenue = $records->whereIn('type', ['sale', 'payment_in'])->sum('amount');
        $totalRefunds = $records->where('type', 'refund')->sum('amount');
        $totalRevenue = $grossRevenue - $totalRefunds;
        $totalExpenses = $records->whereIn('type', ['purchase', 'expense', 'payment_out'])->sum('amount');

        return [
            'branch_id' => $branchId,
            'gross_revenue' => $grossRevenue,
            'total_refunds' => $totalRefunds,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_balance' => $totalRevenue - $totalExpenses,
            'transaction_count' => $records->count(),
            'transactions' => $records->toArray(),
        ];
    }

    public function businessStatement(): array
    {
        $records = CashFlow::with(['branch', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $grossRevenue = $records->whereIn('type', ['sale', 'payment_in'])->sum('amount');
        $totalRefunds = $records->where('type', 'refund')->sum('amount');
        $revenue = $grossRevenue - $totalRefunds;
        $expenses = $records->whereIn('type', ['purchase', 'expense', 'payment_out'])->sum('amount');

        $byBranch = $records->groupBy('business_branch_id')->map(function ($items, $branchId) {
            $gross = $items->whereIn('type', ['sale', 'payment_in'])->sum('amount');
            $ref = $items->where('type', 'refund')->sum('amount');
            $rev = $gross - $ref;
            $exp = $items->whereIn('type', ['purchase', 'expense', 'payment_out'])->sum('amount');
            return [
                'business_branch_id' => $branchId,
                'gross_revenue' => $gross,
                'total_refunds' => $ref,
                'total_revenue' => $rev,
                'total_expenses' => $exp,
                'net' => $rev - $exp,
                'transaction_count' => $items->count(),
            ];
        })->values();

        return [
            'gross_revenue' => $grossRevenue,
            'total_refunds' => $totalRefunds,
            'total_revenue' => $revenue,
            'total_expenses' => $expenses,
            'net_balance' => $revenue - $expenses,
            'total_transactions' => $records->count(),
            'by_branch' => $byBranch->toArray(),
            'recent_transactions' => $records->take(10)->toArray(),
        ];
    }
}
