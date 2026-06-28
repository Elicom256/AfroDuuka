<?php

namespace App\AI\Tools;

use App\AI\Tool;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CustomerPurchases extends Tool
{
    public function name(): string
    {
        return 'customer_purchases';
    }

    public function description(): string
    {
        return 'Get purchase history for a specific customer';
    }

    public function parameters(): array
    {
        return [
            'customer_id' => [
                'type' => 'integer',
                'description' => 'The customer ID to look up',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Number of purchases to return (default 20)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $customerId = $parameters['customer_id'] ?? null;
        $limit = min((int) ($parameters['limit'] ?? 20), 100);

        if (!$customerId) {
            return ['message' => 'Please provide a customer ID.'];
        }

        $customer = \App\Models\Customer::with('user')->find($customerId);

        if (!$customer) {
            return ['message' => 'Customer not found.'];
        }

        $sales = Sale::where('customer_id', $customerId)
            ->with('saleItems.businessBranchProduct', 'businessBranch')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($sale) => [
                'id' => $sale->id,
                'total_amount' => $sale->total_amount,
                'status' => $sale->status,
                'branch' => $sale->businessBranch?->name,
                'items_count' => $sale->saleItems->count(),
                'date' => $sale->created_at->toDateTimeString(),
            ]);

        $totalSpent = (float) Sale::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->sum('total_amount');

        return [
            'customer_name' => $customer->user?->name ?? $customer->company_name ?? "Customer #{$customerId}",
            'customer_code' => $customer->customer_code,
            'total_purchases' => $sales->count(),
            'total_spent' => $totalSpent,
            'recent_purchases' => $sales,
        ];
    }
}
