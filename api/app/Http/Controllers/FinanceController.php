<?php

namespace App\Http\Controllers;

use App\Enums\CashFlowType;
use App\Http\Requests\StoreCashFlowAdjustmentRequest;
use App\Models\CashFlow;
use App\Models\User;
use App\Services\FinanceService;
use App\Support\Auth\RolePermissions;
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

        // Branch ids arrive as query strings and path segments but leave the database as
        // integers, so both sides are compared as strings. Comparing the raw values with
        // in_array's strict flag made a user ask for their *own* branch get a 403,
        // because '1' !== 1.
        $permitted = array_map('strval', $branchIds);

        if ($branchId) {
            if (! in_array((string) $branchId, $permitted, true)) {
                abort(403, 'Branch is not within your allowed scope');
            }

            return (string) $branchId;
        }

        if (count($permitted) === 1) {
            return $permitted[0];
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
        // The role gate and the branch scope check both live above the try. They end in
        // abort(), which throws an HttpException -- an \Exception -- so inside the try a
        // legitimate 403 was caught and answered as 422 "Failed to fetch transactions".
        // Same arrangement as adjustment(), where the gates live in the request.
        $this->authorizeSensitiveFinance();

        $branchId = $request->filled('branch_id')
            ? $this->resolveBranchId($request->branch_id, Auth::user())
            : null;

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
            if ($branchId !== null) {
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
        // Both the gate and the lookup sit above the try: a role that may not read the
        // books gets a 403 and an unknown id gets a 404, rather than both being
        // flattened into 422 by the catch below.
        $this->authorizeSensitiveFinance();

        // Every relation a cash-flow row can point at, so the detail view resolves the
        // source document without a second round trip. Each is null when the row was
        // written by a different kind of transaction.
        //
        // Customer and Supplier carry no name column: a person's name lives on the user
        // they belong to and a company's lives in company_name. Without customer.user the
        // detail view has nothing to render but an id.
        $cashFlow = CashFlow::with([
            'branch', 'createdBy', 'customer.user', 'supplier.user',
            'sale', 'purchase', 'saleReturn', 'purchaseReturn',
            'stockTransfer', 'expense',
        ])->findOrFail($id);

        try {
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
            $validated['type'] = CashFlowType::Adjustment->value;

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
        $this->authorizeSensitiveFinance();
        $branchId = $this->resolveBranchId($request->query('branch_id'), Auth::user());

        try {
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
        $this->authorizeSensitiveFinance();
        $branchId = $this->resolveBranchId($request->query('branch_id'), Auth::user());

        try {
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
        $this->authorizeSensitiveFinance();
        $branchId = $this->resolveBranchId($request->query('branch_id'), Auth::user());

        try {
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
        $this->authorizeSensitiveFinance();
        $branchId = $this->resolveBranchId($branchId, Auth::user());

        try {
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
        $this->authorizeSensitiveFinance();

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
