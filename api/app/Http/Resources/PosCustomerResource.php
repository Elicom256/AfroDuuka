<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'company_name' => $this->company_name,
            'name' => $this->whenLoaded('user', fn() => trim(($this->user?->firstname ?? '') . ' ' . ($this->user?->lastname ?? ''))),
            'phone' => $this->whenLoaded('user', fn() => $this->user?->phone),
            'email' => $this->whenLoaded('user', fn() => $this->user?->email),
            'status' => $this->status,
        ];
    }
}
