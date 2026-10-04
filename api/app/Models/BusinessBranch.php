<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessBranch extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = ['business_id', 'name', 'address', 'phone', 'currency', 'status'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function purchases()
    {
        $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function taxCategories()
    {
        return $this->hasMany(TaxCategory::class);
    }

    public function taxPayments()
    {
        return $this->hasMany(TaxPayment::class);
    }
}
