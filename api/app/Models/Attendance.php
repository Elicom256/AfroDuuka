<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;
    protected $fillable = ["worker_id", "status", "check_in", "check_out", "remarks"];

    protected $casts = ["check_out" => "date", "remarks" => "date"];
    public function worker():BelongsTo{
        return $this->belongsTo(Worker::class);
    }
}
