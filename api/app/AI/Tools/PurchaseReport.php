<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseReport extends Tool
{
    public function name(): string
    {
        return 'purchase_report';
    }

    public function description(): string
    {
        return 'Get a report of purchases including totals, counts, and status breakdown';
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

        $query = Purchase::query();

        if ($period !== 'all') {
            $query->where('created_at', '>=', $this->dateFromPeriod($period));
        }

        $summary = (clone $query)->select(
            DB::raw('COUNT(*) as total_purchases'),
            DB::raw('COALESCE(SUM(total_amount), 0) as total_spent'),
            DB::raw('COALESCE(AVG(total_amount), 0) as average_purchase_value')
        )->first();

        $statusBreakdown = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(total_amount), 0) as amount'))
            ->groupBy('status')
            ->get();

        return [
            'period' => $period,
            'total_purchases' => (int) $summary->total_purchases,
            'total_spent' => (float) $summary->total_spent,
            'average_purchase_value' => (float) $summary->average_purchase_value,
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
