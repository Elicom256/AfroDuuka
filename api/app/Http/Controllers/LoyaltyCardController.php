<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoyaltyCardRequest;
use App\Http\Requests\UpdateLoyaltyCardRequest;
use App\Http\Requests\StoreLoyaltyTransactionRequest;
use App\Models\LoyaltyCard;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

/**
 * Manages customer loyalty cards and point transactions (earn/burn).
 */
class LoyaltyCardController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function index()
    {
        $cards = LoyaltyCard::where('business_id', auth()->user()->business_id)
            ->with(['customer', 'loyaltyProgram'])
            ->get();

        return response()->json(['message' => 'Fetched loyalty cards', 'data' => $cards]);
    }

    public function store(StoreLoyaltyCardRequest $request)
    {
        $card = LoyaltyCard::create($request->validated());
        return response()->json(['message' => 'Loyalty card created', 'data' => $card], 201);
    }

    public function show(LoyaltyCard $loyaltyCard)
    {
        $loyaltyCard->load(['customer', 'loyaltyProgram', 'transactions']);
        return response()->json(['message' => 'Fetched loyalty card', 'data' => $loyaltyCard]);
    }

    public function update(UpdateLoyaltyCardRequest $request, LoyaltyCard $loyaltyCard)
    {
        $loyaltyCard->update($request->validated());
        return response()->json(['message' => 'Loyalty card updated', 'data' => $loyaltyCard]);
    }

    /**
     * Earn points on a sale.
     */
    public function earnPoints(Request $request, LoyaltyCard $loyaltyCard)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            $transaction = $this->loyaltyService->earnPoints(
                $loyaltyCard->id,
                $request->sale_id,
                $request->amount,
                $request->reference
            );
            return response()->json(['message' => 'Points earned', 'data' => $transaction], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Burn (redeem) points.
     */
    public function burnPoints(Request $request, LoyaltyCard $loyaltyCard)
    {
        $request->validate([
            'points' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            $transaction = $this->loyaltyService->burnPoints(
                $loyaltyCard->id,
                $request->points,
                $request->reference
            );
            return response()->json(['message' => 'Points redeemed', 'data' => $transaction]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Adjust points (admin only).
     */
    public function adjustPoints(Request $request, LoyaltyCard $loyaltyCard)
    {
        $request->validate([
            'points' => 'required|numeric',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            $transaction = $this->loyaltyService->adjustPoints(
                $loyaltyCard->id,
                $request->points,
                $request->reference
            );
            return response()->json(['message' => 'Points adjusted', 'data' => $transaction]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(LoyaltyCard $loyaltyCard)
    {
        $loyaltyCard->delete();
        return response()->json(['message' => 'Loyalty card deleted']);
    }
}
