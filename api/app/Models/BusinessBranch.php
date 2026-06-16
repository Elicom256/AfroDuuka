<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessBranch extends BaseModel
{
    use HasFactory;

    protected $fillable = ["business_id", "name", "address", "phone", "currency", "status"];

    public function business(){
        return $this->belongsTo(Business::class);
    }
    public function users(){
        $this->belongsToMany(User::class);
    }
    public function purchases(){
        $this->hasMany(Purchase::class);
    }
    public function sales(){
        $this->hasMany(Sale::class);
    }
}
