<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
    ];

    protected $appends = ['name'];

    public function getNameAttribute(): string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return trim($this->user->firstname.' '.$this->user->lastname);
        }

        return $this->employee_code ?? '';
    }

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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function employeeRemuneration()
    {
        return $this->hasMany(EmployeeRemuneration::class);
    }

    /**
     * The salaries this worker is paid under, reached through the role on their user
     * record. Workers carry no role column of their own — `users.role_id` is the
     * identity layer. Plural because a role can carry more than one salary: one
     * covering every branch and one pinned to a single branch.
     */
    public function salaries(): HasManyThrough
    {
        return $this->hasManyThrough(
            Salary::class,
            User::class,
            'user_id',
            'role_id',
            'id',
            'role_id',
        );
    }
}
