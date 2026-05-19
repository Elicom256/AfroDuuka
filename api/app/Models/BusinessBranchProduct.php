<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BusinessBranchProduct extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessBranchProductFactory> */
    use HasFactory;

    protected $fillable = [
        'business_branch_id',
        'product_id',
        'quantity',
        'cost_price',
        'price',
        'reorder_level',
        'description',
        'status',
    ];

     public function saleItems(): BelongsToMany
    {
        return $this->belongsToMany(SaleItem::class);
    }

     public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
