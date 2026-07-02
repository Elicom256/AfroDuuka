<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    /** @use HasFactory<\Database\Factories\PricingFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        "mark",
        'description',
        'monthly_price',
        'yearly_price',
        'features',
        'limits',
        'is_active',
        'sort_order',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'limits' => 'array',
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
}
