<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Worker extends Model
{
    protected $fillable = [
        'user_id',
        'employee_code',
        'department',
        'position',
        'employment_type',
        'salary',
        'hire_date',
        'status',
        'remarks',
    ];

    /**
     * Worker belongs to a User (identity layer)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}