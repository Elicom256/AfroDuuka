<?php

namespace App\Services;

use App\Models\CashFlow;
use Illuminate\Support\Carbon;
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

        // The ledger is the only source of truth for cash. Balance used to be read from
        // a stored running_balance column, written by runningBalance(), which nothing
        // called, so every row stayed null and this reported 0 for every business.
        // Summing the rows cannot go stale: inserting, editing or deleting a refund is
        // reflected immediately, with no recalculation step there to forget.
        $cashBalance = $this->netCashMovement(clone $query);

        $recentTransactions = (clone $query)
            ->with(['branch', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $this->attachRunningBalances($recentTransactions, $query);

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

    /**
     * Net cash movement of the ledger, optionally counting only the rows ordered before
     * a given position.
     *
     * The CASE mirrors CashFlow::cashEffect(). The two are kept honest by
     * FinanceCashBalanceTest, which asserts the headline balance equals the running
     * balance of the newest row.
     */
    private function netCashMovement($query, ?Carbon $position = null, ?int $beforeId = null): float
    {
        $net = (clone $query)
            ->when($position !== null, function ($q) use ($position, $beforeId) {
                $q->where(function ($q) use ($position, $beforeId) {
                    $q->where('created_at', '<', $position)
                        ->orWhere(function ($tie) use ($position, $beforeId) {
                            $tie->where('created_at', $position)
                                ->when($beforeId !== null, fn ($t) => $t->where('id', '<', $beforeId));
                        });
                });
            })
            ->selectRaw(
                "COALESCE(SUM(CASE
                    WHEN direction = 'credit' THEN amount
                    WHEN direction = 'debit' THEN -amount
                    WHEN type IN ('sale', 'payment_in') THEN amount
                    WHEN type IN ('purchase', 'expense', 'payment_out', 'refund') THEN -amount
                    ELSE 0
                END), 0) as net"
            )
            ->value('net');

        return (float) $net;
    }

    /**
     * Give each listed row the cash balance that follows it, oldest first.
     *
     * The listed rows are only the tail of the ledger, so the walk starts from the
     * balance of everything ordered before the oldest of them. The value is attached to
     * the model for rendering and then marked clean, so it reads as a derived figure
     * and cannot be written back as if it were stored state.
     */
    private function attachRunningBalances($transactions, $baseQuery): void
    {
        if ($transactions->isEmpty()) {
            return;
        }

        $oldest = $transactions->last();
        $balance = $this->netCashMovement(clone $baseQuery, $oldest->created_at, $oldest->id);

        foreach ($transactions->reverse() as $transaction) {
            $balance += $transaction->cashEffect();
            $transaction->running_balance = round($balance, 2);
        }

        $transactions->each->syncOriginal();
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
