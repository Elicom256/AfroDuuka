<?php

namespace App\Http\Controllers;

use App\Enums\CashFlowDirection;
use App\Http\Requests\StoreCashFlowRequest;
use App\Http\Requests\UpdateCashFlowDirectionRequest;
use App\Http\Requests\UpdateCashFlowRequest;
use App\Models\CashFlow;
use App\Services\CashFlowService;
use App\Services\UnsignedAdjustmentResolver;

class CashFlowController extends Controller
{
    protected CashFlowService $cashFlowService;

    protected UnsignedAdjustmentResolver $unsignedAdjustments;

    public function __construct(CashFlowService $cashFlowService, UnsignedAdjustmentResolver $unsignedAdjustments)
    {
        $this->cashFlowService = $cashFlowService;
        $this->unsignedAdjustments = $unsignedAdjustments;
    }

    /**
     * Display a paginated listing of cashflows.
     */
    public function index()
    {
        // Chronological, not insertion order: the date shown on each row is
        // transaction_date, and a backdated entry belongs beside the other rows from
        // that day rather than wherever it happened to be typed. id breaks ties so the
        // order, and therefore each running balance, is stable within a day.
        $cashFlow = CashFlow::with(['branch', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'message' => 'Fetched cashflow records',
            'data' => $cashFlow,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * Only manual adjustments reach here — the request confines `type` to that one value,
     * because every other type is written by CashFlowService as a consequence of the sale,
     * purchase or return that caused it, and a client supplying one would be inventing
     * revenue the reports then total.
     *
     * No try/catch, deliberately. The pattern used elsewhere in FinanceController turned a
     * role refusal into a 422 "Failed to create adjustment" that a caller could not tell
     * from a bad payload, and swallowed the real message. Authorization is settled by the
     * request's authorize() and validation failures are rendered by the framework, so
     * there is nothing here worth catching: an unexpected exception should stay a 500 and
     * stay visible.
     */
    public function store(StoreCashFlowRequest $request)
    {
        $cashFlow = CashFlow::create($request->validated());

        return response()->json([
            'message' => 'Cash flow created successfully',
            'data' => $cashFlow,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashFlow $cashFlow)
    {
        return response()->json(['message' => 'Fetched cashflow', 'data' => $cashFlow]);
    }

    /**
     * Analytics
     */
    public function analytics()
    {
        try {
            $period = request()->query('period', 'last_7_days');
            $allowedPeriods = ['last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'last_year'];
            if (! in_array($period, $allowedPeriods)) {
                $period = 'last_7_days'; // fallback
            }
            $cashFlow = $this->cashFlowService->analytics($period);

            return response()->json([
                'message' => 'Fetched inventory analytics!',
                'data' => $cashFlow,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch inventory analytics!',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCashFlowRequest $request, CashFlow $cashFlow)
    {
        $cashFlow->update($request->validated());

        return response()->json([
            'message' => 'Cash flow updated successfully!',
            'data' => $cashFlow,
        ]);
    }

    /**
     * Record which way the money moved on an adjustment that never said.
     *
     * Adjustments written before a direction became required are excluded from the cash
     * balance, because CashFlow::cashEffect() returns 0 for one and the reported figure is
     * quietly short. FinanceService::dashboard() counts them so the gap is visible; this is
     * where the count gets closed.
     *
     * Route model binding resolves through the tenant scope on BaseModel, so a cash flow
     * belonging to another business is a 404 before this runs. Deliberately not
     * withoutGlobalScopes(), which is how the artisan command reaches across businesses.
     */
    public function setDirection(UpdateCashFlowDirectionRequest $request, CashFlow $cashFlow)
    {
        $refusal = $this->unsignedAdjustments->refusalFor($cashFlow);

        if ($refusal !== null) {
            return response()->json([
                'message' => $refusal,
            ], 422);
        }

        $this->unsignedAdjustments->apply($cashFlow, CashFlowDirection::from($request->validated('direction')));

        return response()->json([
            'message' => "Direction recorded as {$cashFlow->direction}. This adjustment now moves the cash balance.",
            'data' => $cashFlow,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashFlow $cashFlow)
    {
        $cashFlow->delete();

        return response()->json(['message' => 'Deleted cashflow!']);
    }
}
