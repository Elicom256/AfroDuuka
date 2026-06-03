<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, "business_branch_id");
    }
    public function attendance():HasMany{
        return $this->hasMany(Attendance::class);
    }
    public function employeeRemuneration(){
        return $this->hasMany(EmployeeRemuneration::class);
    }
}