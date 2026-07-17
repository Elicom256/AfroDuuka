<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeldCart extends BaseModel
{
    protected $fillable = [
        'business_branch_id',
        'user_id',
        'customer_id',
        'items',
        'notes',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
