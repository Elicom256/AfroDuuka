<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialAuditRequest;
use App\Http\Requests\UpdateFinancialAuditRequest;
use App\Models\FinancialAudit;
use App\Services\FinancialAuditService;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialAuditController extends Controller
{
    protected FinancialAuditService $auditService;
    protected ActivityLogService $activityLog;

    public function __construct(FinancialAuditService $auditService, ActivityLogService $activityLog)
    {
        $this->auditService = $auditService;
        $this->activityLog = $activityLog;
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = FinancialAudit::with(['branch', 'performedBy', 'approvedBy'])
            ->where('business_id', $user->business_id);

        if ($request->filled('business_branch_id')) {
            $query->where('business_branch_id', $request->business_branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('audit_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('audit_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('audit_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $audits = $query->orderByDesc('audit_date')->paginate(10);

        return response()->json([
            'message' => 'Fetched financial audits',
            'audits' => $audits,
        ]);
    }

    public function store(StoreFinancialAuditRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $auditNumber = $this->auditService->generateAuditNumber($validated['business_branch_id']);

        $audit = $this->auditService->createAudit([
            'business_id' => $user->business_id,
            'business_branch_id' => $validated['business_branch_id'],
            'audit_number' => $auditNumber,
            'audit_date' => $validated['audit_date'],
            'expected_balance' => $validated['expected_balance'] ?? 0,
            'actual_balance' => $validated['actual_balance'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'performed_by' => $user->id,
        ]);

        $this->activityLog->activity(
            'Created Financial Audit',
            "Created financial audit #{$auditNumber}"
        );

        return response()->json([
            'message' => 'Financial audit created successfully',
            'audit' => $audit->load(['branch', 'performedBy']),
        ], 201);
    }

    public function show(FinancialAudit $financialAudit): JsonResponse
    {
        $user = Auth::user();
        if ($financialAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $financialAudit->load(['branch', 'performedBy', 'approvedBy']);

        return response()->json([
            'message' => 'Fetched financial audit',
            'audit' => $financialAudit,
        ]);
    }

    public function update(UpdateFinancialAuditRequest $request, FinancialAudit $financialAudit): JsonResponse
    {
        $user = Auth::user();
        if ($financialAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($financialAudit->status, ['draft', 'in_progress'])) {
            return response()->json(['message' => 'Only draft or in-progress audits can be edited'], 422);
        }

        $validated = $request->validated();
        $data = [
            'audit_date' => $validated['audit_date'] ?? $financialAudit->audit_date,
            'expected_balance' => $validated['expected_balance'] ?? $financialAudit->expected_balance,
            'actual_balance' => $validated['actual_balance'] ?? $financialAudit->actual_balance,
            'notes' => $validated['notes'] ?? $financialAudit->notes,
            'status' => $validated['status'] ?? $financialAudit->status,
        ];
        $data['difference'] = $data['actual_balance'] - $data['expected_balance'];

        $financialAudit->update($data);

        return response()->json([
            'message' => 'Financial audit updated successfully',
            'audit' => $financialAudit->load(['branch', 'performedBy']),
        ]);
    }

    public function destroy(FinancialAudit $financialAudit): JsonResponse
    {
        $user = Auth::user();
        if ($financialAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($financialAudit->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Only draft or cancelled audits can be deleted'], 422);
        }

        $financialAudit->delete();

        return response()->json([
            'message' => 'Financial audit deleted successfully',
        ]);
    }

    public function approve(FinancialAudit $financialAudit): JsonResponse
    {
        $user = Auth::user();
        if ($financialAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if ($financialAudit->status !== 'completed') {
            return response()->json(['message' => 'Only completed audits can be approved'], 422);
        }

        $audit = $this->auditService->approveAudit($financialAudit);

        return response()->json([
            'message' => 'Financial audit approved successfully',
            'audit' => $audit,
        ]);
    }

    public function cancel(FinancialAudit $financialAudit): JsonResponse
    {
        $user = Auth::user();
        if ($financialAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if (in_array($financialAudit->status, ['approved', 'cancelled'])) {
            return response()->json(['message' => 'Audit cannot be cancelled'], 422);
        }

        $audit = $this->auditService->cancelAudit($financialAudit);

        return response()->json([
            'message' => 'Financial audit cancelled successfully',
            'audit' => $audit,
        ]);
    }

    public function report(FinancialAudit $financialAudit): JsonResponse
    {
        $user = Auth::user();
        if ($financialAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $financialAudit->load(['branch', 'performedBy', 'approvedBy']);

        return response()->json([
            'message' => 'Fetched financial audit report',
            'audit' => $financialAudit,
        ]);
    }
}
