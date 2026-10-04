<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Database\Factories\EmployeeRemunerationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRemuneration extends BaseModel
{
    /** @use HasFactory<EmployeeRemunerationFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'worker_id',
        'amount',
        'type',
        'payment_date',
        'reference',
        'description',
        'status',
        'business_id',
        'business_branch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }
}
