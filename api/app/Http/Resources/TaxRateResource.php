<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_category_id' => $this->tax_category_id,
            'name' => $this->name,
            'rate' => (float) $this->rate,
            'jurisdiction_zone' => $this->jurisdiction_zone,
            'is_active' => (bool) $this->is_active,
        ];
    }
}