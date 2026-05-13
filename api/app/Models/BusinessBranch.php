<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessBranch extends BaseModel
{
    use HasFactory;

    protected $fillable = ["business_id", "name", "address", "status"];

    public function business(){
        $this->belongsTo(Business::class);
    }
    public function workers(){
        $this->belongsTo(User::class);
    }
    public function purchases(){
        $this->belongsTo(Purchase::class);
    }
    public function sales(){
        $this->belongsTo(Sale::class);
    }
}
