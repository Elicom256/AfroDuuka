<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Worker extends BaseModel
{
    use LogsActivity;

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
        'business_id',
        'business_branch_id',
    ];

    /**
     * Worker belongs to a User (identity layer)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBusinessBranchAttribute()
{
    return $this->user?->businessBranch ?? null;
}
    public function attendances():HasMany{
        return $this->hasMany(Attendance::class);
    }
    public function employeeRemuneration(){
        return $this->hasMany(EmployeeRemuneration::class);
    }
    public function employeeSalaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }
}