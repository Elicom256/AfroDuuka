<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessCreditRequest;
use App\Models\BusinessCredit;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessCreditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BusinessCredit::with(['customer'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('business_branch_id')) {
            $query->where('business_branch_id', $request->business_branch_id);
        }

        return response()->json([
            'message' => 'Fetched business credits',
            'data' => $query->paginate(20),
        ]);
    }

    public function store(StoreBusinessCreditRequest $request): JsonResponse
    {
        // The customer-side mirror of a supplier debit: it asserts the business is
        // owed money, so it is a ledger decision rather than a floor one.
        abort_unless(RolePermissions::canManageBranch($request->user()), 403, 'You cannot open customer credits.');

        $credit = BusinessCredit::create($request->validated());

        return response()->json([
            'message' => 'Business credit created',
            'data' => $credit,
        ], 201);
    }

    public function show(BusinessCredit $businessCredit): JsonResponse
    {
        $businessCredit->load(['customer']);

        return response()->json([
            'message' => 'Fetched business credit',
            'data' => array_merge(
                $businessCredit->toArray(),
                [
                    'amount_paid' => $businessCredit->amountPaid(),
                    'balance' => $businessCredit->balance(),
                    'lifecycle_status' => $businessCredit->lifecycle_status,
                    'is_overdue' => $businessCredit->is_overdue,
                ]
            ),
        ]);
    }

    public function update(StoreBusinessCreditRequest $request, BusinessCredit $businessCredit): JsonResponse
    {
        abort_unless(RolePermissions::canManageBranch($request->user()), 403, 'You cannot edit customer credits.');

        $businessCredit->update($request->validated());

        return response()->json([
            'message' => 'Credit updated successfully',
            'data' => $businessCredit->fresh(['customer']),
        ]);
    }

    public function destroy(BusinessCredit $businessCredit): JsonResponse
    {
        $businessCredit->delete();

        return response()->json([
            'message' => 'Business credit deleted',
        ]);
    }

    public function overdue(): JsonResponse
    {
        $credits = BusinessCredit::overdue()
            ->with(['customer'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (BusinessCredit $credit) => [
                'id' => $credit->id,
                'customer' => $credit->customer?->company_name,
                'amount' => (float) $credit->amount,
                'balance' => $credit->balance(),
                'due_date' => $credit->due_date?->toDateString(),
                'days_overdue' => $credit->due_date?->diffInDays(now()),
            ]);

        return response()->json([
            'message' => 'Overdue credits',
            'data' => $credits,
        ]);
    }
}
