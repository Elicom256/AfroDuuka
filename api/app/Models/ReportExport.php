<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks asynchronously generated report exports (CSV, XLSX, PDF).
 * Reports are generated via queued jobs and stored for download.
 */
class ReportExport extends BaseModel
{
    protected $fillable = [
        'business_id',
        'user_id',
        'report_type',
        'format',
        'parameters',
        'file_path',
        'status',
        'generated_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'json',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
