<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class BusinessTaxes extends BaseModel
{
    /** @use HasFactory<\Database\Factories\BusinessTaxesFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_id',
        // 'business_branch_id',
        'name',
        'rate',
        'type',
        'description',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
