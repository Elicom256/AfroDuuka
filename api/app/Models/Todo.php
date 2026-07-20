<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Todo extends BaseModel
{
    /** @use HasFactory<\Database\Factories\TodoFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'business_id',
        'user_id',
        'title',
        'description',
        'date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
