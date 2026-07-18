<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromotionController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $promotions = Promotion::where('business_branch_id', $user->business_branch_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['message' => 'Promotions fetched', 'data' => $promotions]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:active,inactive,expired',
            'max_uses' => 'nullable|integer|min:1',
        ]);

        $promotion = Promotion::create([
            'business_id' => $user->business_id,
            'business_branch_id' => $user->business_branch_id,
            ...$validated,
        ]);

        return response()->json(['message' => 'Promotion created', 'data' => $promotion], 201);
    }

    public function show(Promotion $promotion): JsonResponse
    {
        return response()->json(['message' => 'Promotion fetched', 'data' => $promotion]);
    }

    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:active,inactive,expired',
            'max_uses' => 'nullable|integer|min:1',
        ]);

        $promotion->update($validated);

        return response()->json(['message' => 'Promotion updated', 'data' => $promotion]);
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        $promotion->delete();
        return response()->json(['message' => 'Promotion deleted']);
    }
}
