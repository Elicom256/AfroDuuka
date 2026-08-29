<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductAuditRequest;
use App\Http\Requests\UpdateProductAuditRequest;
use App\Models\ProductAudit;
use App\Services\ProductAuditService;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductAuditController extends Controller
{
    protected ProductAuditService $auditService;
    protected ActivityLogService $activityLog;

    public function __construct(ProductAuditService $auditService, ActivityLogService $activityLog)
    {
        $this->auditService = $auditService;
        $this->activityLog = $activityLog;
    }

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = ProductAudit::with(['items.product', 'branch', 'performedBy', 'approvedBy'])
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
            'message' => 'Fetched product audits',
            'audits' => $audits,
        ]);
    }

    public function store(StoreProductAuditRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $auditNumber = $this->auditService->generateAuditNumber($validated['business_branch_id']);

        $audit = $this->auditService->createAudit([
            'business_id' => $user->business_id,
            'business_branch_id' => $validated['business_branch_id'],
            'audit_number' => $auditNumber,
            'audit_date' => $validated['audit_date'],
            'status' => $validated['status'] ?? 'draft',
            'notes' => $validated['notes'] ?? null,
            'performed_by' => $user->id,
        ], $validated['items']);

        $this->activityLog->activity(
            'Created Product Audit',
            "Created product audit #{$auditNumber}"
        );

        return response()->json([
            'message' => 'Product audit created successfully',
            'audit' => $audit,
        ], 201);
    }

    public function show(ProductAudit $productAudit): JsonResponse
    {
        $user = Auth::user();
        if ($productAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $productAudit->load(['items.product', 'branch', 'performedBy', 'approvedBy']);

        return response()->json([
            'message' => 'Fetched product audit',
            'audit' => $productAudit,
        ]);
    }

    public function update(UpdateProductAuditRequest $request, ProductAudit $productAudit): JsonResponse
    {
        $user = Auth::user();
        if ($productAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($productAudit->status, ['draft', 'in_progress'])) {
            return response()->json(['message' => 'Only draft or in-progress audits can be edited'], 422);
        }

        $validated = $request->validated();

        $productAudit->update([
            'audit_date' => $validated['audit_date'] ?? $productAudit->audit_date,
            'status' => $validated['status'] ?? $productAudit->status,
            'notes' => $validated['notes'] ?? $productAudit->notes,
        ]);

        if ($request->has('items')) {
            $productAudit->items()->delete();
            $this->auditService->createAuditItems($productAudit, $validated['items']);
        }

        $productAudit->load(['items.product', 'branch', 'performedBy']);

        return response()->json([
            'message' => 'Product audit updated successfully',
            'audit' => $productAudit,
        ]);
    }

    public function destroy(ProductAudit $productAudit): JsonResponse
    {
        $user = Auth::user();
        if ($productAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($productAudit->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Only draft or cancelled audits can be deleted'], 422);
        }

        $productAudit->items()->delete();
        $productAudit->delete();

        return response()->json([
            'message' => 'Product audit deleted successfully',
        ]);
    }

    public function approve(ProductAudit $productAudit): JsonResponse
    {
        $user = Auth::user();
        if ($productAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if ($productAudit->status !== 'completed') {
            return response()->json(['message' => 'Only completed audits can be approved'], 422);
        }

        $audit = $this->auditService->approveAudit($productAudit);

        return response()->json([
            'message' => 'Product audit approved successfully',
            'audit' => $audit,
        ]);
    }

    public function cancel(ProductAudit $productAudit): JsonResponse
    {
        $user = Auth::user();
        if ($productAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        if (in_array($productAudit->status, ['approved', 'cancelled'])) {
            return response()->json(['message' => 'Audit cannot be cancelled'], 422);
        }

        $audit = $this->auditService->cancelAudit($productAudit);

        return response()->json([
            'message' => 'Product audit cancelled successfully',
            'audit' => $audit,
        ]);
    }

    public function report(ProductAudit $productAudit): JsonResponse
    {
        $user = Auth::user();
        if ($productAudit->business_id !== $user->business_id) {
            abort(403, 'Unauthorized');
        }

        $productAudit->load(['items.product', 'branch', 'performedBy', 'approvedBy']);

        return response()->json([
            'message' => 'Fetched product audit report',
            'audit' => $productAudit,
        ]);
    }
}
