<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class ProfitSummary extends Tool
{
    public function name(): string
    {
        return 'profit_summary';
    }

    public function description(): string
    {
        return 'Get a profit summary comparing revenue from sales against cost of goods sold';
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

        $dateFrom = $period !== 'all' ? $this->dateFromPeriod($period) : null;

        $revenueQuery = SaleItem::whereHas('sale', fn ($q) => $q->where('status', 'completed'));
        $costQuery = PurchaseItem::whereHas('purchase', fn ($q) => $q->where('status', 'completed'));

        if ($dateFrom) {
            $revenueQuery->where('created_at', '>=', $dateFrom);
            $costQuery->where('created_at', '>=', $dateFrom);
        }

        $totalRevenue = (float) (clone $revenueQuery)->sum('subtotal');
        $totalCost = (float) (clone $costQuery)->sum(DB::raw('quantity * cost_price'));
        $grossProfit = $totalRevenue - $totalCost;
        $margin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'period' => $period,
            'total_revenue' => $totalRevenue,
            'total_cost_of_goods' => $totalCost,
            'gross_profit' => $grossProfit,
            'profit_margin_percent' => $margin,
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
