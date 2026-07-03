<?php

namespace App\Models\CoreSettings;

use App\Models\BaseModel;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends BaseModel
{
    /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
    use HasFactory;

    protected $fillable = ["business_id", "method", "status"];

    public function business(){
        return $this->belongsTo(Business::class);
    }
}
