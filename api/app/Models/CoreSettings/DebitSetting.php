<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Database\Factories\DebitSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DebitSetting extends BaseModel
{
    /** @use HasFactory<DebitSettingFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
