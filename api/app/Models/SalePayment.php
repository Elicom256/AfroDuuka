<?php

namespace App\Models;

use Database\Factories\SalePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalePayment extends Model
{
    /** @use HasFactory<SalePaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['sale_id', 'method', 'amount', 'paymentStatus'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
