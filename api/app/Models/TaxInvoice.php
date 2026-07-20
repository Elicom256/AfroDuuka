<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

/**
 * URA-compliant tax invoice linked to a sale.
 * Contains QR code data for EFRIS integration.
 */
class TaxInvoice extends BaseModel
{
    use LogsActivity;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'sale_id',
        'invoice_number',
        'ura_qr_code',
        'vat_amount',
        'total_amount',
        'submitted_to_ura',
        'submitted_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'submitted_to_ura' => 'boolean',
            'submitted_at' => 'datetime',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function businessBranch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
