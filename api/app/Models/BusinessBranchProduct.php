<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessBranchProduct extends BaseModel
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
}
