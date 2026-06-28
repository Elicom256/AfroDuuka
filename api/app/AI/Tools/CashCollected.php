<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;

class CashCollected extends Tool
{
    public function name(): string
    {
        return 'cash_collected';
    }

    public function description(): string
    {
        return 'Get the total cash collected, broken down by payment method';
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

        $query = SalePayment::where('paymentStatus', 'paid');

        if ($period !== 'all') {
            $query->where('created_at', '>=', $this->dateFromPeriod($period));
        }

        $total = (clone $query)->sum('amount');

        $byMethod = (clone $query)
            ->select('method', DB::raw('COALESCE(SUM(amount), 0) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->get();

        return [
            'period' => $period,
            'total_collected' => (float) $total,
            'by_method' => $byMethod,
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
