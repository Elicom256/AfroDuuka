<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 
        'customer_code', 
        'company_name',
        'status',
        'remarks'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
