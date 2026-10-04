<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Database\Factories\CreditSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditSetting extends BaseModel
{
    /** @use HasFactory<CreditSettingFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
