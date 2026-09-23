<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_branch_id' => $this->business_branch_id,
            'tax_category_id' => $this->tax_category_id,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'tax_period_start' => $this->tax_period_start?->toDateString(),
            'tax_period_end' => $this->tax_period_end?->toDateString(),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'tax_category' => $this->whenLoaded('taxCategory', fn () => [
                'id' => $this->taxCategory->id,
                'name' => $this->taxCategory->name,
            ]),
        ];
    }
}