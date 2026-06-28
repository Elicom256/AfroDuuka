<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class ComparePeriods extends Tool
{
    public function name(): string
    {
        return 'compare_periods';
    }

    public function description(): string
    {
        return 'Compare business performance between two periods (sales, purchases, profit)';
    }

    public function parameters(): array
    {
        return [
            'period1' => [
                'type' => 'string',
                'description' => 'First period: last_7_days, last_30_days, this_month, last_month',
                'default' => 'last_month',
            ],
            'period2' => [
                'type' => 'string',
                'description' => 'Second period: last_7_days, last_30_days, this_month, last_month',
                'default' => 'this_month',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $period1 = $parameters['period1'] ?? 'last_month';
        $period2 = $parameters['period2'] ?? 'this_month';

        $data1 = $this->periodData($period1);
        $data2 = $this->periodData($period2);

        $salesChange = $data1['sales'] > 0
            ? round((($data2['sales'] - $data1['sales']) / $data1['sales']) * 100, 2)
            : null;

        $purchasesChange = $data1['purchases'] > 0
            ? round((($data2['purchases'] - $data1['purchases']) / $data1['purchases']) * 100, 2)
            : null;

        return [
            'comparison' => "{$period2} vs {$period1}",
            'period1' => [
                'label' => $period1,
                'sales' => $data1['sales'],
                'purchases' => $data1['purchases'],
                'sales_count' => $data1['sales_count'],
            ],
            'period2' => [
                'label' => $period2,
                'sales' => $data2['sales'],
                'purchases' => $data2['purchases'],
                'sales_count' => $data2['sales_count'],
            ],
            'changes' => [
                'sales_change_percent' => $salesChange,
                'purchases_change_percent' => $purchasesChange,
            ],
        ];
    }

    protected function periodData(string $period): array
    {
        $dates = $this->getDateRange($period);

        $sales = (float) Sale::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('status', 'completed')
            ->sum('total_amount');

        $salesCount = Sale::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('status', 'completed')
            ->count();

        $purchases = (float) Purchase::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('status', 'completed')
            ->sum('total_amount');

        return compact('sales', 'salesCount', 'purchases');
    }

    protected function getDateRange(string $period): array
    {
        return match ($period) {
            'last_7_days' => [
                'start' => now()->subDays(7)->startOfDay(),
                'end' => now(),
            ],
            'last_30_days' => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now(),
            ],
            'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now(),
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            default => [
                'start' => now()->subDays(30),
                'end' => now(),
            ],
        };
    }
}
