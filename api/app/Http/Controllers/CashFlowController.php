<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowRequest;
use App\Http\Requests\UpdateCashFlowRequest;
use App\Models\CashFlow;
use App\Services\CashFlowService;
use Illuminate\Support\Facades\Auth;

class CashFlowController extends Controller
{
    protected CashFlowService $cashFlowService;
    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cashFlow = CashFlow::all();
        return response()->json(["message" => "Fetched monthly cashflow", "data" => $cashFlow]);
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
        'data' => $cashFlow
    ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashFlow $cashFlow)
    {
        return response()->json(["message" => "Fetched cashflow", "data" => $cashFlow]);
    }


 /**
     * Analytics
     */
    public function analytics()
    {
        try {
            $period = request()->query('period', 'last_7_days');
            $allowedPeriods = ['last_7_days', 'last_30_days', 'this_month', 'last_month', 'this_year', 'last_year'];
            if (!in_array($period, $allowedPeriods)) {
                $period = 'last_7_days'; // fallback
            }
            $business_branch_id = Auth::user()->business_branch_id;
            // $cFlow = CashFlow::where("business_branch_id", $business_branch_id)->get();
            $cashFlow = $this->cashFlowService->analytics($business_branch_id, $period);
            return response()->json([
            "message" => "Failed to fetch inventory analytics!",
            "data" => $cashFlow
           ]);
        } catch (\Exception $e) {
           return response()->json([
            "message" => "Failed to fetch inventory analytics!",
            "error" => $e->getMessage()
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
        'data' => $cashFlow
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashFlow $cashFlow)
    {
        $cashFlow->delete();
        return response()->json(["message" => "Deleted cashflow!"]);
    }
}
