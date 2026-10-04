<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends BaseModel
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = ['business_branch_id', 'worker_id', 'status', 'check_in', 'check_out', 'remarks'];

    protected $casts = ['check_out' => 'date'];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
