<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends BaseModel
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'supplier_code',
        'company_name',
        'status',
        'business_id',
        'business_branch_id',
    ];

    protected $appends = ['name'];

    public function getNameAttribute(): string
    {
        if ($this->company_name) {
            return $this->company_name;
        }

        if ($this->relationLoaded('user') && $this->user) {
            return trim($this->user->firstname.' '.$this->user->lastname);
        }

        return 'Supplier';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
