<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashFlowRequest;
use App\Http\Requests\UpdateCashFlowRequest;
use App\Models\CashFlow;

class CashFlowController extends Controller
{
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
