<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Customer extends BaseModel
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 
        'customer_code', 
        'company_name',
        'status',
        'remarks',
        'business_id',
        'business_branch_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
