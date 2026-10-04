<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCategory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'business_branch_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }

    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    public function taxPayments(): HasMany
    {
        return $this->hasMany(TaxPayment::class);
    }
}
