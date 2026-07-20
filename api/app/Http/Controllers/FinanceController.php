<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowRequest;
use App\Models\CashFlow;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function dashboard()
    {
        try {
            $user = Auth::user();
            $branchId = request()->query('branch_id');
            $data = $this->financeService->dashboard($user->business_id, $branchId);

            return response()->json([
                'message' => 'Fetched finance dashboard',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch finance dashboard',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function transactions(Request $request)
    {
        try {
            $user = Auth::user();
            $query = CashFlow::where('business_id', $user->business_id)
                ->with(['branch', 'createdBy']);

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }
            if ($request->filled('branch_id')) {
                $query->where('business_branch_id', $request->branch_id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                });
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'message' => 'Fetched transactions',
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function transaction($id)
    {
        try {
            $user = Auth::user();
            $cashFlow = CashFlow::where('business_id', $user->business_id)
                ->with(['branch', 'createdBy', 'customer', 'supplier', 'sale', 'purchase'])
                ->findOrFail($id);

            return response()->json([
                'message' => 'Fetched transaction',
                'data' => $cashFlow,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transaction not found',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function adjustment(StoreCashFlowRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['type'] = 'adjustment';

            $cashFlow = CashFlow::create($validated);

            return response()->json([
                'message' => 'Adjustment created successfully',
                'data' => $cashFlow,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create adjustment',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function revenueReport(Request $request)
    {
        try {
            $user = Auth::user();
            $data = $this->financeService->revenueReport(
                $user->business_id,
                $request->query('branch_id'),
                $request->query('start_date', now()->startOfMonth()->toDateString()),
                $request->query('end_date', now()->toDateString()),
                $request->query('group_by', 'day')
            );

            return response()->json([
                'message' => 'Fetched revenue report',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch revenue report',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function expenseReport(Request $request)
    {
        try {
            $user = Auth::user();
            $data = $this->financeService->expenseReport(
                $user->business_id,
                $request->query('branch_id'),
                $request->query('start_date', now()->startOfMonth()->toDateString()),
                $request->query('end_date', now()->toDateString())
            );

            return response()->json([
                'message' => 'Fetched expense report',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch expense report',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function incomeSummary(Request $request)
    {
        try {
            $user = Auth::user();
            $data = $this->financeService->incomeSummary(
                $user->business_id,
                $request->query('branch_id'),
                $request->query('year', now()->format('Y'))
            );

            return response()->json([
                'message' => 'Fetched income summary',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch income summary',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function branchStatement($branchId)
    {
        try {
            $user = Auth::user();
            $data = $this->financeService->branchStatement($user->business_id, $branchId);

            return response()->json([
                'message' => 'Fetched branch financial statement',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch branch statement',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function businessStatement()
    {
        try {
            $user = Auth::user();
            $data = $this->financeService->businessStatement($user->business_id);

            return response()->json([
                'message' => 'Fetched business financial statement',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch business statement',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
