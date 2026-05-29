<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessBranchProduct extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessBranchProductFactory> */
    use HasFactory;

    protected $fillable = [
        'business_branch_id',
        'product_id',
        'quantity',
        'name',
        'cost_price',
        'price',
        'markup_percentage',
        'reorder_level',
        'description',
        'status',
        'last_sold_at'
    ];

    protected $casts = ["last_sold_at"];
     public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

     public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
