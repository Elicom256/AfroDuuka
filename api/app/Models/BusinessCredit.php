<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessCredit extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'business_branch_id',
        'customer_id',
        'amount',
        'reference',
        'status',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}