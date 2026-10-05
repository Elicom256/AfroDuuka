<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowRequest;
use App\Http\Requests\UpdateCashFlowRequest;
use App\Models\CashFlow;
use App\Services\CashFlowService;

class CashFlowController extends Controller
{
    protected CashFlowService $cashFlowService;

    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
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
     */
    public function store(StoreCashFlowRequest $request)
    {
        $validated = $request->validated();
        $cashFlow = CashFlow::create($validated);

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
     * Remove the specified resource from storage.
     */
    public function destroy(CashFlow $cashFlow)
    {
        $cashFlow->delete();

        return response()->json(['message' => 'Deleted cashflow!']);
    }
}
