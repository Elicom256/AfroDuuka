<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Database\Factories\PromotionsSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromotionsSettings extends BaseModel
{
    /** @use HasFactory<PromotionsSettingsFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
