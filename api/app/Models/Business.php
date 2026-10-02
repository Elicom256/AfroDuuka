<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class Business extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'business_category_id', 'country_id', 'timezone', "status", "subscription_balance", "logo"
        ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}