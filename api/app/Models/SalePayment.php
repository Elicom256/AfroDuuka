<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    /** @use HasFactory<\Database\Factories\SalePaymentFactory> */
    use HasFactory;
    protected $fillable = ["sale_id", "method", "amount", "paymentStatus"];

    public function sale(){
       return $this->belongsTo(Sale::class);
    }
}
