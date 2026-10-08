<?php

namespace App\Services;

use App\Models\FinancialAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialAuditService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function generateAuditNumber(int $businessBranchId): string
    {
        $count = FinancialAudit::where('business_branch_id', $businessBranchId)->count() + 1;

        return 'FAUDIT-'.str_pad($businessBranchId, 4, '0', STR_PAD_LEFT).'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function createAudit(array $data): FinancialAudit
    {
        return DB::transaction(function () use ($data) {
            $data['difference'] = ($data['actual_balance'] ?? 0) - ($data['expected_balance'] ?? 0);

            return FinancialAudit::create($data);
        });
    }

    /**
     * The audit trail is written inside the transaction on purpose: a log entry recording
     * an approval that then failed to commit is worse than no entry at all. This call
     * used to call a static ActivityLog::log() that does not exist, so it threw, and
     * because it threw in here the whole approval rolled back.
     */
    public function approveAudit(FinancialAudit $audit): FinancialAudit
    {
        return DB::transaction(function () use ($audit) {
            $audit->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->activityLog->activity(
                'approved_financial_audit',
                "Approved financial audit #{$audit->audit_number}",
                subject: $audit,
            );

            return $audit->fresh(['branch', 'performedBy', 'approvedBy']);
        });
    }

    public function cancelAudit(FinancialAudit $audit): FinancialAudit
    {
        $audit->update(['status' => 'cancelled']);

        $this->activityLog->activity(
            'cancelled_financial_audit',
            "Cancelled financial audit #{$audit->audit_number}",
            subject: $audit,
        );

        return $audit->fresh(['branch', 'performedBy']);
    }
}
