<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SalesSummary extends Tool
{
    public function name(): string
    {
        return 'sales_summary';
    }

    public function description(): string
    {
        return 'Get a summary of sales including total sales, count, average order value, and status breakdown';
    }

    public function parameters(): array
    {
        return [
            'period' => [
                'type' => 'string',
                'description' => 'Period: last_7_days, last_30_days, this_month, last_month, or all',
                'default' => 'last_30_days',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $period = $parameters['period'] ?? 'last_30_days';

        $query = Sale::query();

        if ($period !== 'all') {
            $query->where('created_at', '>=', $this->dateFromPeriod($period));
        }

        $summary = (clone $query)->select(
            DB::raw('COUNT(*) as total_sales'),
            DB::raw('COALESCE(SUM(total_amount), 0) as total_revenue'),
            DB::raw('COALESCE(AVG(total_amount), 0) as average_order_value')
        )->first();

        $statusBreakdown = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(total_amount), 0) as amount'))
            ->groupBy('status')
            ->get();

        return [
            'period' => $period,
            'total_sales' => (int) $summary->total_sales,
            'total_revenue' => (float) $summary->total_revenue,
            'average_order_value' => (float) $summary->average_order_value,
            'by_status' => $statusBreakdown,
        ];
    }

    protected function dateFromPeriod(string $period): string
    {
        $date = match ($period) {
            'last_7_days' => now()->subDays(7),
            'last_30_days' => now()->subDays(30),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(30),
        };
        return $date->toDateTimeString();
    }
}
