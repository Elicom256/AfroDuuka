<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Database\Factories\ReportsSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportsSettings extends BaseModel
{
    /** @use HasFactory<ReportsSettingsFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
