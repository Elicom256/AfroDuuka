<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salary extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'role_id',
        'amount',
        'period',
        'status',
        'set_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period' => 'string',
        'status' => 'enum:active,inactive',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Worker::class, 'salary_id');
    }
}
