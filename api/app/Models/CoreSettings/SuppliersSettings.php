<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Database\Factories\SuppliersSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SuppliersSettings extends BaseModel
{
    /** @use HasFactory<SuppliersSettingsFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
