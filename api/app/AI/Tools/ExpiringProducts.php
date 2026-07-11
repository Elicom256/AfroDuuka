<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ExpiringProducts extends Tool
{
    public function name(): string
    {
        return 'expiring_products';
    }

    public function description(): string
    {
        return 'Get products that are expired, expiring soon, or in the danger zone. Use "expired" for past expiry, "expiring" for upcoming expiry within N days, "danger" for within 7 days.';
    }

    public function parameters(): array
    {
        return [
            'days' => [
                'type' => 'integer',
                'description' => 'Number of days to look ahead for expiry (default 30)',
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Filter: expired, expiring, danger, or all (default all)',
                'enum' => ['expired', 'expiring', 'danger', 'all'],
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $days = isset($parameters['days']) ? (int) $parameters['days'] : 30;
        $status = $parameters['status'] ?? 'all';
        $branchId = Auth::user()->business_branch_id;

        $now = now()->startOfDay();
        $dangerDate = (clone $now)->addDays(7);
        $expiryWindow = (clone $now)->addDays($days);

        $baseQuery = Product::whereNotNull('expiry_date')
            ->with('productCategory')
            ->where('business_branch_id', $branchId);

        $expired = (clone $baseQuery)
            ->where('expiry_date', '<', $now)
            ->orderBy('expiry_date')
            ->get();

        $expiring = (clone $baseQuery)
            ->whereBetween('expiry_date', [$now, $expiryWindow])
            ->orderBy('expiry_date')
            ->get();

        $danger = (clone $baseQuery)
            ->whereBetween('expiry_date', [$now, $dangerDate])
            ->orderBy('expiry_date')
            ->get();

        $allProducts = collect();

        if ($status === 'expired') {
            $allProducts = $expired;
        } elseif ($status === 'expiring') {
            $allProducts = $expiring;
        } elseif ($status === 'danger') {
            $allProducts = $danger;
        } else {
            $allProducts = $expired->merge($expiring)->unique('id');
        }

        $mapped = $allProducts->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'quantity' => $p->quantity,
            'expiry_date' => $p->expiry_date->format('Y-m-d'),
            'status' => $p->expiry_date->isPast() ? 'expired' : 'active',
            'days_remaining' => $p->expiry_date->isPast()
                ? -$p->expiry_date->diffInDays($now)
                : $now->diffInDays($p->expiry_date),
        ]);

        return [
            'products' => $mapped,
            'summary' => [
                'expired_count' => $expired->count(),
                'expiring_count' => $expiring->count(),
                'danger_count' => $danger->count(),
                'total_with_expiry' => (clone $baseQuery)->count(),
            ],
        ];
    }
}
