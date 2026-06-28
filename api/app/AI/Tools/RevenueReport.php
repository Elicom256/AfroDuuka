<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\CashFlow;
use Illuminate\Support\Facades\DB;

class RevenueReport extends Tool
{
    public function name(): string
    {
        return 'revenue_report';
    }

    public function description(): string
    {
        return 'Get a revenue report showing income from sales and other inflows';
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

        $query = CashFlow::where('type', 'sale')->where('status', 'completed');

        if ($period !== 'all') {
            $query->where('transaction_date', '>=', $this->dateFromPeriod($period));
        }

        $totalRevenue = (clone $query)->sum('amount');

        $byCategory = (clone $query)
            ->select('category', DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('category')
            ->get();

        $daily = (clone $query)
            ->select(DB::raw("DATE(transaction_date) as date"), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'period' => $period,
            'total_revenue' => (float) $totalRevenue,
            'by_category' => $byCategory,
            'daily_breakdown' => $daily,
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
        return $date->startOfDay()->toDateString();
    }
}
