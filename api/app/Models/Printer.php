<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a thermal receipt printer connected to a branch.
 * Supports bluetooth, USB, and network-connected printers.
 */
class Printer extends BaseModel
{
    protected $fillable = [
        'business_id',
        'business_branch_id',
        'name',
        'type',
        'ip_address',
        'port',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
