<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoyaltyRewardRequest;
use App\Http\Requests\UpdateLoyaltyRewardRequest;
use App\Models\LoyaltyReward;

/**
 * Manages redeemable rewards in the loyalty program.
 */
class LoyaltyRewardController extends Controller
{
    public function index()
    {
        $rewards = LoyaltyReward::where('business_id', auth()->user()->business_id)
            ->where('is_active', true)
            ->get();

        return response()->json(['message' => 'Fetched loyalty rewards', 'data' => $rewards]);
    }

    public function store(StoreLoyaltyRewardRequest $request)
    {
        $reward = LoyaltyReward::create($request->validated());
        return response()->json(['message' => 'Loyalty reward created', 'data' => $reward], 201);
    }

    public function show(LoyaltyReward $loyaltyReward)
    {
        return response()->json(['message' => 'Fetched loyalty reward', 'data' => $loyaltyReward]);
    }

    public function update(UpdateLoyaltyRewardRequest $request, LoyaltyReward $loyaltyReward)
    {
        $loyaltyReward->update($request->validated());
        return response()->json(['message' => 'Loyalty reward updated', 'data' => $loyaltyReward]);
    }

    public function destroy(LoyaltyReward $loyaltyReward)
    {
        $loyaltyReward->delete();
        return response()->json(['message' => 'Loyalty reward deleted']);
    }
}
