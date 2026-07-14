<?php

/*-----------------------------------------------------------------------------------
 * Service: PriceHistoryService
 * -------------------------------
 * Contains business logic for retrieving and analysing price change history.
 * Analytics uses the same period-based pattern as other analytics in the project.
 *---------------------------------------------------------------------------------*/

namespace App\Services;

use App\Models\PriceHistory;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PriceHistoryService
{
    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    /**
     * Get paginated price timeline for a single product.
     */
    public function getProductTimeline(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return PriceHistory::with('changedByUser')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get latest price changes across all products, with optional filters.
     */
    public function getLatestChanges(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PriceHistory::with('product')->orderBy('created_at', 'desc');

        // Filter by specific product
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Price change analytics grouped by day over a given period.
     * Fills missing dates with zeroes so charts render continuous lines.
     */
    public function priceChangeAnalytics(string $period = 'last_7_days'): array
    {
        $dates = $this->analyticsTrendHelper->getPeriodDates($period);
        $days = $this->analyticsTrendHelper->getDaysFromPeriod($period);

        // Get daily count of price changes and average change amounts
        $rawTrend = PriceHistory::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw("DATE_FORMAT(created_at, '%b %d') as date")
            ->selectRaw("COUNT(*) as count")
            ->selectRaw("COALESCE(AVG(new_cost_price - old_cost_price), 0) as avg_cost_change")
            ->selectRaw("COALESCE(AVG(new_sale_price - old_sale_price), 0) as avg_sale_change")
            ->groupBy('date')
            ->orderBy('created_at')
            ->get()
            ->keyBy('date');

        // Fill missing dates with zero values so chartjs renders a continuous line
        $trend = [];
        $startDate = Carbon::now()->subDays($days - 1);
        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateLabel = $currentDate->format('M d');

            $existing = $rawTrend->get($dateLabel);

            $trend[] = [
                'date'            => $dateLabel,
                'count'           => $existing->count ?? 0,
                'avg_cost_change' => round((float) ($existing->avg_cost_change ?? 0), 2),
                'avg_sale_change' => round((float) ($existing->avg_sale_change ?? 0), 2),
            ];
        }

        // Aggregate totals
        $totalChanges = PriceHistory::whereBetween('created_at', [$dates['start'], $dates['end']])->count();

        // Most-changed product (top 5)
        $mostChanged = PriceHistory::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw('product_id')
            ->selectRaw('COUNT(*) as changes')
            ->groupBy('product_id')
            ->orderByDesc('changes')
            ->with('product')
            ->take(5)
            ->get();

        return [
            'period'        => $period,
            'total_changes' => $totalChanges,
            'trend'         => $trend,
            'most_changed'  => $mostChanged,
        ];
    }
}
