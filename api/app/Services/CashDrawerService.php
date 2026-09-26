<?php

namespace App\Services;

use App\Models\CashDrawerSession;
use App\Models\SalePayment;
use App\Models\User;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashDrawerService
{
    public function open(User $user, float $openingCash, ?int $branchId = null, float $allowedVariance = 0): CashDrawerSession
    {
        $branchId ??= $user->business_branch_id;
        $this->assertBranchAccess($user, $branchId);

        return DB::transaction(function () use ($user, $openingCash, $branchId, $allowedVariance) {
            $existing = CashDrawerSession::where('business_branch_id', $branchId)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new InvalidArgumentException('This branch already has an open cash drawer session.');
            }

            return CashDrawerSession::create([
                'business_id' => $user->business_id,
                'business_branch_id' => $branchId,
                'opened_by' => $user->id,
                'opening_cash' => $openingCash,
                'allowed_variance' => $allowedVariance,
                'status' => 'open',
                'opened_at' => now(),
            ]);
        });
    }

    public function close(User $user, CashDrawerSession $session, float $countedCash): CashDrawerSession
    {
        $this->assertBranchAccess($user, $session->business_branch_id);

        return DB::transaction(function () use ($user, $session, $countedCash) {
            $locked = CashDrawerSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'open') {
                throw new InvalidArgumentException('This cash drawer session is already closed.');
            }

            $cashSales = SalePayment::query()
                ->where('method', 'cash')
                ->where('paymentStatus', 'paid')
                ->whereHas('sale', function ($query) use ($locked) {
                    $query->where('business_branch_id', $locked->business_branch_id)
                        ->whereBetween('created_at', [$locked->opened_at, now()]);
                })
                ->sum('amount');

            $expectedCash = round((float) $locked->opening_cash + (float) $cashSales, 2);
            $variance = round($countedCash - $expectedCash, 2);

            if (abs($variance) > (float) $locked->allowed_variance) {
                throw new InvalidArgumentException(
                    "Cash drawer does not reconcile. Expected {$expectedCash}, counted {$countedCash}, variance {$variance}."
                );
            }

            $locked->update([
                'expected_cash' => $expectedCash,
                'counted_cash' => $countedCash,
                'variance' => $variance,
                'closed_by' => $user->id,
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            return $locked->fresh(['branch', 'openedBy', 'closedBy']);
        });
    }

    private function assertBranchAccess(User $user, int $branchId): void
    {
        $resolved = EffectiveBranchScope::branchesFor($user);
        if ($resolved !== null && ! in_array($branchId, $resolved[1], true)) {
            throw new InvalidArgumentException('The selected business branch is outside your scope.');
        }
    }
}
