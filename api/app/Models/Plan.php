<?php

namespace App\Models;

use App\Models\CoreSettings\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    protected $fillable = [
        "business_id",
        "pricing_id",
        "status",
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
