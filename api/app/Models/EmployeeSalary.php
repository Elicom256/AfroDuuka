<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $business_branch_id
 * @property int $role_id
 * @property decimal $amount
 * @property string $period
 * @property string $status
 * @property int|null $set_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class EmployeeSalary extends BaseModel
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

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
