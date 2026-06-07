<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessTaxPaymentRequest;
use App\Http\Requests\UpdateBusinessTaxPaymentRequest;
use App\Models\BusinessTaxPayment;
use App\Http\Resources\BusinessTaxPaymentResource;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessTaxPaymentsController extends Controller
{
      protected ActivityLogService $activity_log;
    public function __construct(ActivityLogService $activityLog)
    {
        $this->activity_log = $activityLog;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BusinessTaxPayment::with(['businessBranch', 'businessTax', 'createdBy']);

        // Filtering
        $totalTax = 0;
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

        // Sorting & Pagination
        // $perPage = $request->input('per_page', 15);

        $payments = $query->latest()->paginate(10);
        $totalTax = $query->sum("paid_amount");
        $outstanding = $query->sum("balance");
        return response()->json(["paidTaxes" => $payments, "totalTax" => $totalTax, "outstanding" => $outstanding]);
        // return BusinessTaxPaymentResource::collection($payments, $totalTax, $outstanding);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessTaxPaymentRequest $request)
    {
        $data = $request->validated();
        
        // Auto set created_by if not provided
        $data['created_by'] = $data['created_by'] ?? Auth::user()->id;

        $payment = BusinessTaxPayment::create($data);
        $this->activity_log->activity(
            "Paid Tax", $payment->paid_amount . " ". "has been paid as a tax for taxId $payment->business_tax_id"
        );


        return response()->json([
            'message' => 'Tax payment recorded successfully',
            'data' => new BusinessTaxPaymentResource($payment)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessTaxPayment $businessTaxPayment)
    {
        $businessTaxPayment->load(['businessBranch', 'businessTax', 'createdBy', 'updatedBy']);

        return new BusinessTaxPaymentResource($businessTaxPayment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessTaxPaymentRequest $request, BusinessTaxPayment $businessTaxPayment)
    {
        $validated = $request->validated();

        $businessTaxPayment = $businessTaxPayment->update($validated);
// new BusinessTaxPaymentResource($businessTaxPayment->fresh())
        return response()->json([
            'message' => 'Tax payment updated successfully',
            'data' => $businessTaxPayment
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessTaxPayment $businessTaxPayment)
    {
        $businessTaxPayment->delete(); // Soft delete

        return response()->json([
            'message' => 'Tax payment deleted successfully'
        ]);
    }

    /**
     * Force delete (optional - for admin only)
     */
    public function forceDestroy(BusinessTaxPayment $businessTaxPayment)
    {
        $businessTaxPayment->forceDelete();

        return response()->json([
            'message' => 'Tax payment permanently deleted'
        ]);
    }
}