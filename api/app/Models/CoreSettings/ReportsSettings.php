<?php

namespace App\Models\CoreSettings;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportsSettings extends Model
{
    /** @use HasFactory<\Database\Factories\ReportsSettingsFactory> */
    use HasFactory;

     protected $fillable = ["business_id", "status"];

    public function business(){
        return $this->belongsTo(Business::class);
    }
}
