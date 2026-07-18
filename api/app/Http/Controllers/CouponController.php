<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $coupons = Coupon::where('business_branch_id', $user->business_branch_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['message' => 'Coupons fetched', 'data' => $coupons]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'sometimes|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,expired',
        ]);

        $businessName = $user->business->name ?? 'BUSI';
        $prefix = strtoupper(substr($businessName, 0, 4));
        $couponCount = Coupon::where('business_id', $user->business_id)->count();
        $code = $prefix . str_pad($couponCount + 1, 3, '0', STR_PAD_LEFT);

        $coupon = Coupon::create([
            'business_id' => $user->business_id,
            'business_branch_id' => $user->business_branch_id,
            'code' => $code,
            ...$validated,
        ]);

        return response()->json(['message' => 'Coupon created', 'data' => $coupon], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return response()->json(['message' => 'Coupon fetched', 'data' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'nullable|string',
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'sometimes|numeric|min:0',
            'valid_from' => 'sometimes|date',
            'valid_until' => 'sometimes|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,expired',
        ]);

        $coupon->update($validated);

        return response()->json(['message' => 'Coupon updated', 'data' => $coupon]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        return response()->json(['message' => 'Coupon deleted']);
    }
}
