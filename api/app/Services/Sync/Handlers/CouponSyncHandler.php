<?php

namespace App\Services\Sync\Handlers;

use App\Models\Coupon;
use App\Models\User;
use App\Services\CouponService;
use App\Services\Sync\SyncAction;
use App\Services\Sync\SyncHandler;
use App\Support\Auth\RolePermissions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;

/**
 * Coupon create goes through CouponService so the code sequence stays the one
 * the online store uses. The rules match CouponController.
 */
class CouponSyncHandler implements SyncHandler
{
    public function __construct(private readonly CouponService $coupons)
    {
    }

    public function type(): string
    {
        return 'coupon';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync coupons.');
        }

        $action = SyncAction::name($payload);

        if ($action === 'delete') {
            if (! RolePermissions::canDelete($user)) {
                throw new AuthorizationException('You cannot delete coupons.');
            }
            $coupon = Coupon::query()->findOrFail(SyncAction::id($payload));
            $id = (int) $coupon->getKey();
            $coupon->delete();

            return SyncAction::result('coupon', $id);
        }

        if (! RolePermissions::canManageDiscounts($user)) {
            throw new AuthorizationException('You cannot create coupons.');
        }

        $validated = Validator::make(SyncAction::body($payload), [
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'sometimes|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,expired',
        ])->validate();

        $coupon = $this->coupons->create($validated, $user);

        return SyncAction::result('coupon', (int) $coupon->getKey(), [
            'code' => $coupon->code,
        ]);
    }
}
