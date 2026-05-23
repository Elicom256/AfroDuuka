<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuppliersSettings extends BaseModel
{
    /** @use HasFactory<\Database\Factories\SuppliersSettingsFactory> */
    use HasFactory;

     protected $fillable = ["business_id", "status"];

    public function business(){
        return $this->belongsTo(Business::class);
    }
}
