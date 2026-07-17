<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'cost_price' => (float) $this->cost_price,
            'stock' => (int) $this->quantity,
            'reorder_level' => (int) $this->reorder_level,
            'category' => $this->whenLoaded('productCategory', fn() => $this->productCategory?->name),
            'status' => $this->status,
        ];
    }
}
