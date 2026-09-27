<?php

namespace App\Services;

use App\Models\CustomerCreditTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Support\Tenant\EffectiveBranchScope;
use App\Models\SalePayment;
use InvalidArgumentException;

class CustomerCreditService
{
    public function recordCharge(User $user, Sale $sale, float $amount): CustomerCreditTransaction
    {
        if (! $sale->customer_id) {
            throw new InvalidArgumentException('A credit sale requires a customer.');
        }

        return CustomerCreditTransaction::create([
            'business_branch_id' => $sale->business_branch_id,
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'created_by' => $user->id,
            'type' => 'charge',
            'amount' => $amount,
            'reference' => 'SALE-' . $sale->id,
            'notes' => 'POS credit sale',
        ]);
    }

    public function recordPayment(User $user, int $customerId, int $branchId, float $amount, ?string $reference = null, ?string $notes = null): CustomerCreditTransaction
    {
        $this->assertBranchAccess($user, $branchId);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit payment must be greater than zero.');
        }
        if ($amount > $this->balance($customerId, $branchId)) {
            throw new InvalidArgumentException('Credit payment cannot exceed the customer balance.');
        }

        return CustomerCreditTransaction::create([
            'business_branch_id' => $branchId,
            'customer_id' => $customerId,
            'created_by' => $user->id,
            'type' => 'payment',
            'amount' => $amount,
            'reference' => $reference,
            'notes' => $notes,
        ]);
    }

    public function recordRefund(User $user, Sale $sale, float $amount): ?CustomerCreditTransaction
    {
        if (! $sale->customer_id) {
            return null;
        }

        $creditPaid = (float) SalePayment::where('sale_id', $sale->id)
            ->where('method', 'credit')
            ->where('paymentStatus', 'paid')
            ->sum('amount');
        $refundAmount = min($amount, $creditPaid);

        if ($refundAmount <= 0) {
            return null;
        }

        return CustomerCreditTransaction::create([
            'business_branch_id' => $sale->business_branch_id,
            'customer_id' => $sale->customer_id,
            'sale_id' => $sale->id,
            'created_by' => $user->id,
            'type' => 'refund',
            'amount' => $refundAmount,
            'reference' => 'REFUND-' . $sale->id,
            'notes' => 'Credit sale refund',
        ]);
    }

    public function balance(int $customerId, int $branchId): float
    {
        $totals = CustomerCreditTransaction::where('customer_id', $customerId)
            ->where('business_branch_id', $branchId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('charge', 'refund') THEN amount ELSE 0 END), 0) as charges")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as payments")
            ->first();

        return round((float) $totals->charges - (float) $totals->payments, 2);
    }

    public function assertAccess(User $user, int $branchId): void
    {
        $this->assertBranchAccess($user, $branchId);
    }

    private function assertBranchAccess(User $user, int $branchId): void
    {
        $resolved = EffectiveBranchScope::branchesFor($user);
        if ($resolved !== null && ! in_array($branchId, $resolved[1], true)) {
            throw new InvalidArgumentException('The selected business branch is outside your scope.');
        }
    }
}
