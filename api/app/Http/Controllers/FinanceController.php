<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowAdjustmentRequest;
use App\Models\CashFlow;
use App\Models\User;
use App\Services\FinanceService;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    private function resolveBranchId(?string $branchId, ?User $user): ?string
    {
        $resolved = EffectiveBranchScope::branchesFor($user);

        if ($resolved === null) {
            return null;
        }

        [, $branchIds] = $resolved;

        if ($branchId) {
            if (! in_array($branchId, $branchIds, true)) {
                abort(403, 'Branch is not within your allowed scope');
            }

            return $branchId;
        }

        if (count($branchIds) === 1) {
            return (string) $branchIds[0];
        }

        return null;
    }

    private function authorizeSensitiveFinance(): void
    {
        // Compared through RolePermissions rather than against a raw lowercased name.
        // The old comparison needed the role stored as exactly 'branch_manager', so a
        // role stored as 'BranchManager' was refused access to every finance report and
        // statement. It also let Operations through, which canManageSensitiveFinance()
        // does not: Operations runs the floor and may not rewrite the books.
        abort_unless(
            RolePermissions::canManageSensitiveFinance(Auth::user()),
            403,
            'This financial action requires an authorized role.'
        );
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
            $this->authorizeSensitiveFinance();
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

            // Same ordering as CashFlowController::index(), so the running balance
            // attached by FinanceService reads monotonically down this list.
            $transactions = $query
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(15);

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
            $this->authorizeSensitiveFinance();
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

    public function adjustment(StoreCashFlowAdjustmentRequest $request)
    {
        // Both role gates now live in the request's authorize(), so they are settled
        // before validation and can no longer be swallowed by the catch below.
        try {
            $validated = $request->validated();

            // Classified as an adjustment so it moves cash without being counted as
            // revenue or an expense by the type-based reports. The cash sign comes from
            // the required `direction`, which the request now enforces.
            $validated['type'] = 'adjustment';

            // Generated here rather than accepted from the client, matching the
            // 'CF-<KIND>-<id>' style used for every other cash-flow writer. A ULID is
            // used instead of a padded id because an adjustment has no parent row to
            // take an id from, and it keeps the code sortable by creation time.
            $validated['transaction_code'] = 'CF-ADJ-'.Str::ulid();

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
            $this->authorizeSensitiveFinance();
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
            $this->authorizeSensitiveFinance();
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
            $this->authorizeSensitiveFinance();
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
            $this->authorizeSensitiveFinance();
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
            $this->authorizeSensitiveFinance();
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
