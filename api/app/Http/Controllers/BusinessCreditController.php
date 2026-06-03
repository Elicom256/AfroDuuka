<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessCreditRequest;
use App\Http\Requests\UpdateBusinessCreditRequest;
use App\Models\BusinessCredit;
use Illuminate\Support\Facades\Auth;

class BusinessCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessCreditRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessCredit $businessCredit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessCreditRequest $request, BusinessCredit $businessCredit)
    {
        abort_if($businessCredit->business_branch_id !== Auth::user()->business_branch_id,403);
        
        $businessCredit->update($request->validated());
        return response()->json([
        'message' => 'Credit updated successfully',
        'data' => $businessCredit->fresh(['customer']),
    ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessCredit $businessCredit)
    {
        //
    }
}
