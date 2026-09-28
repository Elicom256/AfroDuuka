<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The figures behind a monthly performance report.
 *
 * One document describes one branch. There is deliberately no "company total" variant:
 * a blended figure across every branch reads as if it were somebody's branch, and the
 * person who has to act on it — a branch manager — cannot. Comparisons across branches
 * are Branch Performance's job, on a different card, for a different reader.
 *
 * Returns the payload contract that App\ValueObjects\MonthlyReport parses, so that the
 * downloadable PDF, the emailed PDF and the email body are all describing the same
 * numbers. That matters more than it looks: the notification path freezes these figures
 * into the delivery row when the trigger reserves it, and the download path computes
 * them live, so the two are only guaranteed to agree if they were derived by the same
 * code from the same tables.
 *
 * Sourced from cash_flows rather than from sales/purchases/expenses directly, because
 * cash_flows is what the other report cards on the executive reports page already read, and
 * because it carries business_branch_id, which is what makes per-branch scoping possible
 * at all. Deriving this from the raw documents instead would put a total on screen that
 * contradicts the Branch Performance card two inches above it.
 *
 * Note the type filters below deliberately ignore 'payment_in', 'payment_out',
 * 'refund', 'adjustment' and the stock-transfer types. Those are movements of money
 * that are not trading income, cost or overhead, and folding them in would double-count
 * the underlying sale or purchase.
 */
class MonthlyPerformanceReport
{
    /**
     * @return array<string, mixed> The notification payload contract.
     */
    public function payload(Business $business, BusinessBranch $branch, Carbon $month): array
    {
        [$start, $end] = $this->window($business, $month);

        // [0] because aggregate() hands back a list of rows, and the totals query is
        // ungrouped so that list always holds exactly one row. Reading
        // $totals['total_sales'] off the list looks right and silently yields 0.
        $totals = $this->aggregate($business, $branch, $start, $end)[0] ?? [];

        $sales = (float) ($totals['total_sales'] ?? 0);
        $purchases = (float) ($totals['total_purchases'] ?? 0);
        $expenses = (float) ($totals['total_expenses'] ?? 0);

        return [
            'business_name' => (string) $business->name,
            'branch_name' => (string) $branch->name,
            'period' => $month->format('F Y'),
            'currency' => (string) ($business->country?->currency_code ?: 'UGX'),
            'total_sales' => $sales,
            'total_purchases' => $purchases,
            'total_expenses' => $expenses,
            'total_profit_loss' => round($sales - $purchases - $expenses, 2),
            'number_of_sales' => (int) ($totals['number_of_sales'] ?? 0),
            'number_of_purchases' => (int) ($totals['number_of_purchases'] ?? 0),
            // Kept as a single row so that a consumer which iterates branch rows still
            // works unchanged. The layout hides a one-row table; the name is in the
            // header, which is where a reader of a per-branch document looks for it.
            'branches' => [[
                'name' => (string) $branch->name,
                'sales' => $sales,
                'purchases' => $purchases,
                'expenses' => $expenses,
                'profit_loss' => round($sales - $purchases - $expenses, 2),
            ]],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function aggregate(Business $business, BusinessBranch $branch, string $start, string $end): array
    {
        $sale = "SUM(CASE WHEN cash_flows.type = 'sale' OR cash_flows.category = 'product_sales' THEN cash_flows.amount ELSE 0 END)";

        $query = CashFlow::query()
            ->select([
                DB::raw($sale.' as total_sales'),
                DB::raw("SUM(CASE WHEN cash_flows.type = 'purchase' THEN cash_flows.amount ELSE 0 END) as total_purchases"),
                DB::raw("SUM(CASE WHEN cash_flows.type = 'expense' THEN cash_flows.amount ELSE 0 END) as total_expenses"),
                DB::raw("COUNT(CASE WHEN cash_flows.type = 'sale' OR cash_flows.category = 'product_sales' THEN 1 END) as number_of_sales"),
                DB::raw("COUNT(CASE WHEN cash_flows.type = 'purchase' THEN 1 END) as number_of_purchases"),
            ])
            ->where('cash_flows.business_id', $business->id)
            ->where('cash_flows.status', 'completed')
            // Explicit, and not merely inherited from the branch scope. The scope allows
            // a business executive every branch, so relying on it alone would let a document
            // labelled with one branch's name carry another branch's figures.
            ->where('cash_flows.business_branch_id', $branch->id)
            ->whereBetween('cash_flows.transaction_date', [$start, $end]);

        // No GROUP BY for the company-wide totals. A bare aggregate query already returns
        // exactly one row, and the obvious-looking `groupByRaw('1')` is a trap here:
        // Postgres resolves an ordinal GROUP BY against the select list, so `GROUP BY 1`
        // groups by the first SUM() and fails with "aggregate functions are not allowed
        // in GROUP BY". MySQL tolerates it, which makes it worse — it passes in dev and
        // fails in production.
        //
        // With no matching rows the single row is all NULL, hence the ?? 0 at the call
        // site: an empty month is a report full of zeroes, not a missing row.
        //
        // getAttributes(), not (array) $row and not $row->total_sales. A cast Eloquent
        // model to an array and you get its private properties under NUL-prefixed
        // mangled keys, with the real columns buried in ['attributes']; the totals then
        // read as absent and quietly become 0. Property access only works because the
        // column names are valid, which they are not for a name like total_sales... but
        // it is the same trap with a different disguise.
        return $query->get()
            ->map(fn (CashFlow $row) => $row->getAttributes())
            ->all();
    }

    /**
     * The calendar month, in the business's own timezone.
     *
     * businesses.timezone is a column (see the Business model), which settles the open
     * question in undone.md #2 in favour of the column over a settings row. A business
     * trading across midnight needs its month boundary drawn where its own day ends, not
     * where the server happens to be.
     *
     * @return array{0: string, 1: string}
     */
    private function window(Business $business, Carbon $month): array
    {
        $timezone = $business->timezone ?: config('app.timezone');

        $start = $month->copy()->startOfMonth()->setTimezone($timezone);
        $end = $start->copy()->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }
}
