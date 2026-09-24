<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowRequest;
use App\Models\CashFlow;
use App\Services\FinanceService;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    private function resolveBranchId(?string $branchId, ?\App\Models\User $user): ?string
    {
        if (! $branchId) {
            return null;
        }

        $resolved = EffectiveBranchScope::branchesFor($user);
        if ($resolved !== null) {
            [, $branchIds] = $resolved;
            if (! in_array($branchId, $branchIds, true)) {
                abort(403, 'Branch is not within your allowed scope');
            }
        }

        return $branchId;
    }

    public function dashboard()
    {
        try {
            $user = Auth::user();
            $branchId = $this->resolveBranchId(request()->query('branch_id'), $user);
            $data = $this->financeService->dashboard($branchId);

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
            $query = CashFlow::with(['branch', 'createdBy']);

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
                $branchId = $this->resolveBranchId($request->branch_id, Auth::user());
                $query->where('business_branch_id', $branchId);
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
            $cashFlow = CashFlow::with(['branch', 'createdBy', 'customer', 'supplier', 'sale', 'purchase'])
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
            $branchId = $this->resolveBranchId($request->query('branch_id'), Auth::user());
            $data = $this->financeService->revenueReport(
                $branchId,
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
            $branchId = $this->resolveBranchId($request->query('branch_id'), Auth::user());
            $data = $this->financeService->expenseReport(
                $branchId,
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
            $branchId = $this->resolveBranchId($request->query('branch_id'), Auth::user());
            $data = $this->financeService->incomeSummary(
                $branchId,
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
            $branchId = $this->resolveBranchId($branchId, Auth::user());
            $data = $this->financeService->branchStatement($branchId);

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
            $data = $this->financeService->businessStatement();

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