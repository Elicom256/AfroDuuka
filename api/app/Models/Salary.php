<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A salary is set against a role, not against an individual worker: every worker
 * holding the role is paid `amount`. `business_branch_id` is null when the salary
 * covers every branch, which `EffectiveBranchScope` already reads correctly.
 *
 * @property int $id
 * @property int $business_id
 * @property int|null $business_branch_id
 * @property int $role_id
 * @property string $amount
 * @property string $period
 * @property string $status
 * @property int|null $set_by
 */
class Salary extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const PERIODS = ['monthly', 'yearly'];

    /**
     * Set true to mean "every branch", i.e. a NULL business_branch_id.
     *
     * It has to be a separate flag rather than simply passing null. `BaseModel`'s
     * creating hook fills business_branch_id from the caller's session when the
     * attribute is not set, and it tests with `isset()`, which is also false for an
     * explicit null — so a null passed in is indistinguishable from one omitted and
     * gets overwritten with the creator's branch. `EffectiveBranchScope` documents a
     * NULL business_branch_id as the business-level value, so without this the
     * all-branches salary bugs.md asks for cannot be created at all.
     *
     * Fixing that in BaseModel would change what "unset" means for every model in the
     * application, so the intent is carried here instead.
     */
    public bool $coversAllBranches = false;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'role_id',
        'amount',
        'period',
        'status',
        'set_by',
    ];

    /**
     * `status` is deliberately not cast to a PHP enum: the `enum(...)` cast syntax this
     * file previously carried is not supported in Laravel 13 and raised
     * InvalidCastException on every read. The database check constraint and the form
     * requests are what keep the value to active/inactive.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'period' => 'string',
        'status' => 'string',
    ];

    protected static function booted(): void
    {
        // parent::booted() is load-bearing, not a formality. Laravel calls
        // static::booted() once, and it resolves to the most-derived definition, so
        // declaring booted() here without the parent call silently drops the two
        // tenant global scopes and the business_id/business_branch_id stamp that
        // BaseModel registers. The symptom is invisible in a single-tenant test — the
        // model simply reads across tenants, and business_id lands NULL against a
        // NOT NULL column.
        parent::booted();

        // Registered after the parent's, so it runs second and re-asserts the null
        // that the parent's stamp would otherwise overwrite.
        static::creating(function (self $salary) {
            if ($salary->coversAllBranches) {
                $salary->business_branch_id = null;
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /**
     * The people this salary pays. Workers carry no role column of their own —
     * `users.role_id` is the identity layer — so this is the set of users holding
     * the same role, and their worker record hangs off `user_id`.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }
}
