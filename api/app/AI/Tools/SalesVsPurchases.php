<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class SalesVsPurchases extends Tool
{
    public function name(): string
    {
        return 'sales_vs_purchases';
    }

    public function description(): string
    {
        return 'Compare total sales revenue against total purchase costs';
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

        $salesQuery = Sale::where('status', 'completed');
        $purchasesQuery = Purchase::where('status', 'completed');

        if ($period !== 'all') {
            $dateFrom = $this->dateFromPeriod($period);
            $salesQuery->where('created_at', '>=', $dateFrom);
            $purchasesQuery->where('created_at', '>=', $dateFrom);
        }

        $totalSales = (float) (clone $salesQuery)->sum('total_amount');
        $totalPurchases = (float) (clone $purchasesQuery)->sum('total_amount');
        $difference = $totalSales - $totalPurchases;
        $ratio = $totalPurchases > 0 ? round($totalSales / $totalPurchases, 2) : ($totalSales > 0 ? 'N/A (no purchases)' : 'N/A');

        $salesCount = (clone $salesQuery)->count();
        $purchasesCount = (clone $purchasesQuery)->count();

        return [
            'period' => $period,
            'sales' => [
                'total' => $totalSales,
                'count' => $salesCount,
            ],
            'purchases' => [
                'total' => $totalPurchases,
                'count' => $purchasesCount,
            ],
            'difference' => $difference,
            'ratio' => $ratio,
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
