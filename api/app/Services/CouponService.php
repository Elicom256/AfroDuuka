<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use App\Support\SequenceNumberGenerator;

class CouponService
{
    public function __construct(private readonly SequenceNumberGenerator $sequenceNumbers) {}

    public function create(array $validated, ?User $user): Coupon
    {
        $businessName = $user?->business?->name ?? 'BUSI';
        $prefix = substr($businessName, 0, 4);

        return Coupon::create([
            'code' => $this->sequenceNumbers->couponCode($prefix),
            ...$validated,
        ]);
    }
}