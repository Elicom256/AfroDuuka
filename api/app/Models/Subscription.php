<?php

namespace App\Models;

use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        "business_id",
        "plan_id",
        "payment_method_id",
        "status",
        "start_date",
        "end_date",
        "trial_ends_at",
    ];

    protected function casts(): array
    {
        return [
            "start_date" => "datetime",
            "end_date" => "datetime",
            "trial_ends_at" => "datetime",
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
