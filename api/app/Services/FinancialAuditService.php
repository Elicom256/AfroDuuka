<?php

namespace App\Services;

use App\Models\FinancialAudit;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FinancialAuditService
{
    public function generateAuditNumber(int $businessBranchId): string
    {
        $count = FinancialAudit::where('business_branch_id', $businessBranchId)->count() + 1;
        return 'FAUDIT-' . str_pad($businessBranchId, 4, '0', STR_PAD_LEFT) . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function createAudit(array $data): FinancialAudit
    {
        return DB::transaction(function () use ($data) {
            $data['difference'] = ($data['actual_balance'] ?? 0) - ($data['expected_balance'] ?? 0);

            return FinancialAudit::create($data);
        });
    }

    public function approveAudit(FinancialAudit $audit): FinancialAudit
    {
        return DB::transaction(function () use ($audit) {
            $audit->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            ActivityLog::log(
                Auth::user(),
                'approved_financial_audit',
                $audit,
                "Approved financial audit #{$audit->audit_number}"
            );

            return $audit->fresh(['branch', 'performedBy', 'approvedBy']);
        });
    }

    public function cancelAudit(FinancialAudit $audit): FinancialAudit
    {
        $audit->update(['status' => 'cancelled']);

        ActivityLog::log(
            Auth::user(),
            'cancelled_financial_audit',
            $audit,
            "Cancelled financial audit #{$audit->audit_number}"
        );

        return $audit->fresh(['branch', 'performedBy']);
    }
}
