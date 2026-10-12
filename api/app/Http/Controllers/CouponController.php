<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Support\Auth\RolePermissions;
use App\Support\SequenceNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function __construct(private readonly SequenceNumberGenerator $sequenceNumbers) {}

    public function index(): JsonResponse
    {
        $coupons = Coupon::orderByDesc('created_at')
            ->get();

        return response()->json(['message' => 'Coupons fetched', 'data' => $coupons]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(RolePermissions::canManageDiscounts(Auth::user()), 403, 'You cannot create coupons.');

        $validated = $request->validate($this->couponRules());

        // Allocated from a sequence rather than count() + 1. That count was
        // tenant-scoped while coupons.code is globally unique, so two businesses
        // sharing a four-character name prefix both started at 001 and the second
        // insert always lost — and two requests in the same business could produce
        // the same code, since nothing held the count and the insert together.
        $code = $this->generateCouponCode();

        $coupon = Coupon::create([
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
        abort_unless(RolePermissions::canManageDiscounts(Auth::user()), 403, 'You cannot edit coupons.');

        $validated = $request->validate($this->couponRules(isUpdate: true));

        $coupon->update($validated);

        return response()->json(['message' => 'Coupon updated', 'data' => $coupon]);
    }

    /**
     * Shared validation rules for create/update paths.
     */
    private function couponRules(bool $isUpdate = false): array
    {
        return [
            'description' => 'nullable|string',
            'discount_type' => $isUpdate ? 'sometimes|in:percentage,fixed' : 'required|in:percentage,fixed',
            'discount_value' => $isUpdate ? 'sometimes|numeric|min:0' : 'required|numeric|min:0',
            'min_order_amount' => 'sometimes|numeric|min:0',
            'valid_from' => $isUpdate ? 'sometimes|date' : 'required|date',
            'valid_until' => $isUpdate ? 'sometimes|date|after_or_equal:valid_from' : 'required|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,expired',
        ];
    }

    private function generateCouponCode(): string
    {
        $businessName = Auth::user()?->business?->name ?? 'BUSI';

        return $this->sequenceNumbers->couponCode(substr($businessName, 0, 4));
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        abort_unless(RolePermissions::canDelete(Auth::user()), 403, 'You cannot delete coupons.');

        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted']);
    }
}
