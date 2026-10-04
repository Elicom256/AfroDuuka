<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Database\Factories\CustomersSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomersSettings extends BaseModel
{
    /** @use HasFactory<CustomersSettingsFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
