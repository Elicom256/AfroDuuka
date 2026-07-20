<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class EmployeeRemuneration extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeRemunerationFactory> */
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
