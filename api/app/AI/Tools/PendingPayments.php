<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\SalePayment;

class PendingPayments extends Tool
{
    public function name(): string
    {
        return 'pending_payments';
    }

    public function description(): string
    {
        return 'Get payments that are still pending or partially paid';
    }

    public function parameters(): array
    {
        return [
            'status' => [
                'type' => 'string',
                'description' => 'Filter by status: pending, partial, or all (default all)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $status = $parameters['status'] ?? 'all';

        $query = SalePayment::whereIn('paymentStatus', ['pending', 'partial']);

        if ($status !== 'all' && in_array($status, ['pending', 'partial'])) {
            $query->where('paymentStatus', $status);
        }

        $payments = $query->with('sale')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'sale_id' => $p->sale_id,
                'amount' => $p->amount,
                'method' => $p->method,
                'status' => $p->paymentStatus,
                'date' => $p->created_at->toDateTimeString(),
            ]);

        $totalPending = (float) (clone $query)->sum('amount');
        $count = $payments->count();

        return [
            'payments' => $payments,
            'total_pending_amount' => $totalPending,
            'total_count' => $count,
        ];
    }
}
