<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessBranch extends BaseModel
{
    use HasFactory;

    protected $fillable = ["business_id", "name", "address", "phone", "status"];

    public function business(){
        $this->belongsTo(Business::class);
    }
    public function users(){
        $this->belongsToMany(User::class);
    }
    public function purchases(){
        $this->hasMany(Purchase::class);
    }
    public function sales(){
        $this->belongsTo(Sale::class);
    }
}
