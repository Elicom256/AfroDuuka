<?php

namespace App\Services;

use App\Models\BusinessDebit;
use App\Models\BusinessDebitPayment;
use App\Models\User;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DebtService
{
    public function recordPayment(
        User $user,
        BusinessDebit $debit,
        float $amount,
        ?string $reference = null,
        ?string $notes = null
    ): BusinessDebitPayment {
        $this->assertBranchAccess($user, $debit->business_branch_id);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $outstanding = $debit->balance();

        if ($amount > $outstanding + 0.001) {
            throw new InvalidArgumentException(
                "Payment of {$amount} exceeds the outstanding balance of {$outstanding}."
            );
        }

        return DB::transaction(function () use ($user, $debit, $amount, $reference, $notes) {
            $locked = BusinessDebit::whereKey($debit->id)->lockForUpdate()->firstOrFail();

            $payment = BusinessDebitPayment::create([
                'business_debit_id' => $locked->id,
                'business_branch_id' => $locked->business_branch_id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            if ($locked->balance() - $amount <= 0.001) {
                $locked->update(['status' => 'settled']);
            }

            return $payment;
        });
    }

    public function recalculate(BusinessDebit $debit): array
    {
        $paid = $debit->amountPaid();
        $balance = $debit->balance();
        $status = $balance <= 0 ? 'settled' : ($debit->is_overdue ? 'overdue' : ($paid > 0 ? 'partial' : 'issued'));

        return [
            'amount' => (float) $debit->amount,
            'amount_paid' => $paid,
            'balance' => $balance,
            'lifecycle_status' => $status,
            'is_overdue' => $debit->is_overdue,
        ];
    }

    public function overdueIds(?int $branchId = null): array
    {
        $query = BusinessDebit::overdue();

        if ($branchId !== null) {
            $query->where('business_branch_id', $branchId);
        }

        return $query->pluck('id')->all();
    }

    private function assertBranchAccess(User $user, int $branchId): void
    {
        $resolved = EffectiveBranchScope::branchesFor($user);
        if ($resolved !== null && ! in_array($branchId, $resolved[1], true)) {
            throw new InvalidArgumentException('The selected business branch is outside your scope.');
        }
    }
}
