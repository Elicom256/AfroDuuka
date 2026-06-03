<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessTaxPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            
            // Main Data
            'amount'            => $this->amount,
            'paid_amount'       => $this->paid_amount,
            'balance'           => $this->balance,
            
            'tax_period'        => $this->tax_period,
            'due_date'          => $this->due_date?->format('Y-m-d'),
            'payment_date'      => $this->payment_date?->format('Y-m-d'),
            'paid_at'           => $this->paid_at?->format('Y-m-d H:i:s'),
            
            'status'            => $this->status,
            'status_label'      => $this->status_label,        // from accessor in Model
            
            'reference_number'  => $this->reference_number,
            'payment_method'    => $this->payment_method,
            
            'notes'             => $this->notes,
            
            // Relationships
            'business_branch'   => $this->whenLoaded('businessBranch', function () {
                return [
                    'id'   => $this->businessBranch->id,
                    'name' => $this->businessBranch->name ?? 'N/A',
                ];
            }),
            
            'business_tax'      => $this->whenLoaded('businessTax', function () {
                return [
                    'id'    => $this->businessTax->id,
                    'name'  => $this->businessTax->name ?? 'N/A',
                    'type'  => $this->businessTax->type ?? null,
                ];
            }),
            
            'created_by'        => $this->whenLoaded('createdBy', function () {
                return [
                    'id'   => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ];
            }),
            
            'updated_by'        => $this->whenLoaded('updatedBy', function () {
                return [
                    'id'   => $this->updatedBy->id,
                    'name' => $this->updatedBy->name,
                ];
            }),

            // Timestamps
            'created_at'        => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'        => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at'        => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}