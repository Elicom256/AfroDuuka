<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessTaxPaymentRequest;
use App\Http\Requests\UpdateBusinessTaxPaymentRequest;
use App\Models\BusinessTaxPayment;
use App\Http\Resources\BusinessTaxPaymentResource;
use App\Services\ActivityLogService;
use App\Services\CashFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessTaxPaymentsController extends Controller
{
    protected ActivityLogService $activity_log;
    protected CashFlowService $cashFlowService;

    public function __construct(ActivityLogService $activityLog, CashFlowService $cashFlowService)
    {
        $this->activity_log = $activityLog;
        $this->cashFlowService = $cashFlowService;
    }

    public function index(Request $request)
    {
        $query = BusinessTaxPayment::with(['businessBranch', 'businessTax', 'createdBy']);

        if ($request->has('business_branch_id')) {
            $query->byBranch($request->business_branch_id);
        }
        if ($request->has('tax_period')) {
            $query->forPeriod($request->tax_period);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Calculate totals BEFORE pagination
        $totalTax = (clone $query)->sum("paid_amount");
        $outstanding = (clone $query)->sum("balance");

        $payments = $query->latest()->paginate(10);

        return response()->json([
            "paidTaxes" => $payments,
            "totalTax" => $totalTax,
            "outstanding" => $outstanding
        ]);
    }

    public function store(StoreBusinessTaxPaymentRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $data['created_by'] ?? Auth::id();

        $payment = BusinessTaxPayment::create($data);

        // Record cash outflow for tax payment
        $paidAmount = (float) ($data['paid_amount'] ?? $data['amount'] ?? 0);
        if ($paidAmount > 0) {
            $this->cashFlowService->createCashFlowForTaxPayment($payment, $paidAmount);
        }

        $this->activity_log->activity(
            "Paid Tax",
            "{$payment->paid_amount} has been paid as a tax for taxId {$payment->business_tax_id}"
        );

        return response()->json([
            'message' => 'Tax payment recorded successfully',
            'data' => new BusinessTaxPaymentResource($payment)
        ], 201);
    }

    public function show(BusinessTaxPayment $businessTaxPayment)
    {
        $businessTaxPayment->load(['businessBranch', 'businessTax', 'createdBy', 'updatedBy']);

        return response()->json([
            'message' => 'Tax payment fetched successfully',
            'data' => new BusinessTaxPaymentResource($businessTaxPayment)
        ]);
    }

    public function update(UpdateBusinessTaxPaymentRequest $request, BusinessTaxPayment $businessTaxPayment)
    {
        $validated = $request->validated();

        $businessTaxPayment->update($validated);

        // Record cash outflow if paid_amount was explicitly provided and greater than 0
        if (isset($validated['paid_amount']) && (float) $validated['paid_amount'] > 0) {
            $this->cashFlowService->createCashFlowForTaxPayment($businessTaxPayment, (float) $validated['paid_amount']);
        }

        $this->activity_log->activity(
            "Updated Tax Payment",
            "Tax payment ID {$businessTaxPayment->id} was updated"
        );

        return response()->json([
            'message' => 'Tax payment updated successfully!',
            'data' => $businessTaxPayment
        ]);
    }

    public function destroy(BusinessTaxPayment $businessTaxPayment)
    {
        $id = $businessTaxPayment->id;

        // Optional: log before delete
        $this->activity_log->activity(
            "Deleted Tax Payment",
            "Tax payment ID {$id} was deleted"
        );

        $businessTaxPayment->delete();

        return response()->json([
            'message' => 'Tax payment deleted successfully',
            'deleted_id' => $id
        ]);
    }

    public function forceDestroy(BusinessTaxPayment $businessTaxPayment)
    {
        $id = $businessTaxPayment->id;
        $businessTaxPayment->forceDelete();

        return response()->json([
            'message' => 'Tax payment permanently deleted',
            'deleted_id' => $id
        ]);
    }
}