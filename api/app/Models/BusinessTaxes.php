<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessTaxes extends BaseModel
{
    /** @use HasFactory<\Database\Factories\BusinessTaxesFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'name',
        'rate',
        'type',
        'description',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
