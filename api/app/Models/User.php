<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    protected $fillable = [
        'firstname',
        "lastname",
        "username",
        'email',
        "phone",
        'password',
        "address",
        "nin",
        'business_id',
        'business_branch_id',
        'role_id',
        "status",
        "branch_powers"
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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
    public function worker(){
        return $this->belongsTo(Worker::class);
    }
}