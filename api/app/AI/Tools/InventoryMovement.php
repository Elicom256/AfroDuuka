<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryMovement extends Tool
{
    public function name(): string
    {
        return 'inventory_movement';
    }

    public function description(): string
    {
        return 'Get inventory movement summary (stock in, out, adjustments)';
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

        $query = StockMovement::query();

        if ($period !== 'all') {
            $query->where('created_at', '>=', $this->dateFromPeriod($period));
        }

        $summary = (clone $query)
            ->select(
                'type',
                DB::raw('COALESCE(SUM(quantity), 0) as total_quantity'),
                DB::raw('COUNT(*) as movement_count')
            )
            ->groupBy('type')
            ->get()
           ->keyBy('type');

        $recent = (clone $query)
            ->with('product')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($m) => [
                'product' => $m->product?->name ?? 'Unknown',
                'type' => $m->type,
                'quantity' => $m->quantity,
                'notes' => $m->notes,
                'date' => $m->created_at->toDateTimeString(),
            ]);

        return [
            'period' => $period,
            'stock_in' => [
                'quantity' => (int) ($summary['in']->total_quantity ?? 0),
                'count' => (int) ($summary['in']->movement_count ?? 0),
            ],
            'stock_out' => [
                'quantity' => (int) ($summary['out']->total_quantity ?? 0),
                'count' => (int) ($summary['out']->movement_count ?? 0),
            ],
            'adjustments' => [
                'quantity' => (int) ($summary['adjustment']->total_quantity ?? 0),
                'count' => (int) ($summary['adjustment']->movement_count ?? 0),
            ],
            'recent_movements' => $recent,
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
