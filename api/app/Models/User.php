<?php

namespace App\Models;

use App\Support\Tenant\EffectiveBranchScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes;

    public function scopeTenantVisible($query)
    {
        EffectiveBranchScope::apply($query);

        return $query;
    }

    protected $fillable = [
        'firstname',
        'lastname',
        'username',
        'email',
        'phone',
        'password',
        'address',
        'nin',
        'business_id',
        'business_branch_id',
        'role_id',
        'status',
        'branch_powers',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['name'];

    /**
     * The users table stores the name in two columns; this gives call sites a
     * single display name without each one rebuilding it.
     */
    public function getNameAttribute(): string
    {
        $name = trim(sprintf('%s %s', $this->firstname ?? '', $this->lastname ?? ''));

        return $name !== '' ? $name : (string) $this->email;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function businessBranch()
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
