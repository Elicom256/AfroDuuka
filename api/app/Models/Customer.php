<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends BaseModel
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'customer_code',
        'company_name',
        'status',
        'remarks',
        'business_id',
        'business_branch_id',
    ];

    protected $appends = ['name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getNameAttribute(): string
    {
        if ($this->company_name) {
            return $this->company_name;
        }

        if ($this->relationLoaded('user') && $this->user) {
            return trim($this->user->firstname.' '.$this->user->lastname);
        }

        return 'Customer';
    }
}
