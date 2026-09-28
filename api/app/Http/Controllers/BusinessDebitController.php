<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessDebitRequest;
use App\Models\BusinessDebit;
use App\Services\DebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessDebitController extends Controller
{
    public function __construct(private DebtService $debtService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = BusinessDebit::with(['supplier'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('business_branch_id')) {
            $query->where('business_branch_id', $request->business_branch_id);
        }

        $debts = $query->paginate(20);

        return response()->json([
            'message' => 'Fetched business debits',
            'data' => $debts,
        ]);
    }

    public function store(StoreBusinessDebitRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $debt = BusinessDebit::create($validated);

        return response()->json([
            'message' => 'Business debit created',
            'data' => $debt,
        ], 201);
    }

    public function show(BusinessDebit $businessDebit): JsonResponse
    {
        $businessDebit->load(['supplier', 'payments']);

        return response()->json([
            'message' => 'Fetched business debit',
            'data' => array_merge(
                $businessDebit->toArray(),
                $this->debtService->recalculate($businessDebit)
            ),
        ]);
    }

    public function update(StoreBusinessDebitRequest $request, BusinessDebit $businessDebit): JsonResponse
    {
        $businessDebit->update($request->validated());

        return response()->json([
            'message' => 'Business debit updated',
            'data' => $businessDebit->fresh(),
        ]);
    }

    public function destroy(BusinessDebit $businessDebit): JsonResponse
    {
        $businessDebit->delete();

        return response()->json([
            'message' => 'Business debit deleted',
        ]);
    }

    public function pay(Request $request, BusinessDebit $businessDebit): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment = $this->debtService->recordPayment(
                $request->user(),
                $businessDebit,
                (float) $validated['amount'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Payment recorded',
            'data' => $payment,
            'debt_summary' => $this->debtService->recalculate($businessDebit->fresh()),
        ], 201);
    }

    public function overdue(): JsonResponse
    {
        $debts = BusinessDebit::overdue()
            ->with(['supplier'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (BusinessDebit $debt) => [
                'id' => $debt->id,
                'supplier' => $debt->supplier?->name,
                'amount' => (float) $debt->amount,
                'balance' => $debt->balance(),
                'due_date' => $debt->due_date?->toDateString(),
                'days_overdue' => $debt->due_date?->diffInDays(now()),
            ]);

        return response()->json([
            'message' => 'Overdue debits',
            'data' => $debts,
        ]);
    }
}
