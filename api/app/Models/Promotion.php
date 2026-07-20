<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Promotion extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'status',
        'max_uses',
        'used_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_value' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
