<?php

namespace App\Models;

use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class SubscriptionPayment extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'subscription_id',
        'payment_method_id',
        'amount_paid',
        'transaction_id',
        'number_paid',
        'payment_status',
        'payment_proof',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'notes',
        'business_id',
        'business_branch_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
