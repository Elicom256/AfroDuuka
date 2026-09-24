<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class EmployeeSalary extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'worker_id',
        'amount',
        'currency',
        'effective_date',
        'end_date',
        'status',
        'business_id',
        'business_branch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
