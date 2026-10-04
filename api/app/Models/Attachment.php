<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends BaseModel
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'attachable_type',
        'attachable_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'kind',
    ];

    protected $appends = [
        'url',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk($this->disk ?: 'public')->url($this->path) : null;
    }
}
