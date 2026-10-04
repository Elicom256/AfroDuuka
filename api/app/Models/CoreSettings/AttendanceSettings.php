<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Database\Factories\AttendanceSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceSettings extends BaseModel
{
    /** @use HasFactory<AttendanceSettingsFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
