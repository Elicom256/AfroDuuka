<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class SaleOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $table = "orders";

    protected $fillable = [
        "business_id",
        "business_branch_id",
        "user_id",
        "customer_id",
        "order_number",
        "total_amount",
        "status",
        "notes",
    ];

    protected $casts = [
        "total_amount" => "decimal:2",
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleOrderItem::class, "order_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
