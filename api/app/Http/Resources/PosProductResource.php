<?php

namespace App\Http\Resources;

use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $effectiveRate = app(TaxService::class)->effectiveRateForProduct($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'selling_price' => (float) $this->selling_price,
            'cost_price' => (float) $this->cost_price,
            'is_tax_inclusive' => (bool) $this->is_tax_inclusive,
            'tax_category_id' => $this->tax_category_id,
            'tax_rate' => $effectiveRate ? (float) $effectiveRate->rate : null,
            'markup_percentage' => $this->markup_percentage,
            'stock' => (int) $this->quantity,
            'reorder_level' => (int) $this->reorder_level,
            'category' => $this->whenLoaded('productCategory', fn () => $this->productCategory?->name),
            'status' => $this->status,
        ];
    }
}
