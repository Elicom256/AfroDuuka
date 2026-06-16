<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoyaltyProgramRequest;
use App\Http\Requests\UpdateLoyaltyProgramRequest;
use App\Models\LoyaltyProgram;

/**
 * Manages loyalty program configuration (points, stamps, tiered).
 */
class LoyaltyProgramController extends Controller
{
    public function index()
    {
        $programs = LoyaltyProgram::where('business_id', auth()->user()->business_id)->get();
        return response()->json(['message' => 'Fetched loyalty programs', 'data' => $programs]);
    }

    public function store(StoreLoyaltyProgramRequest $request)
    {
        $program = LoyaltyProgram::create($request->validated());
        return response()->json(['message' => 'Loyalty program created', 'data' => $program], 201);
    }

    public function show(LoyaltyProgram $loyaltyProgram)
    {
        return response()->json(['message' => 'Fetched loyalty program', 'data' => $loyaltyProgram]);
    }

    public function update(UpdateLoyaltyProgramRequest $request, LoyaltyProgram $loyaltyProgram)
    {
        $loyaltyProgram->update($request->validated());
        return response()->json(['message' => 'Loyalty program updated', 'data' => $loyaltyProgram]);
    }

    public function destroy(LoyaltyProgram $loyaltyProgram)
    {
        $loyaltyProgram->delete();
        return response()->json(['message' => 'Loyalty program deleted']);
    }
}
