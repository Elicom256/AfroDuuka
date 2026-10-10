<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        // The audit's branch is fixed at creation; items on an edit must be products of
        // that same branch, never a bare exists:products,id (mirrors StoreProductAuditRequest).
        $branchId = (int) $this->route('productAudit')->business_branch_id;

        return [
            'audit_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => [
                'required_with:items',
                Rule::exists('products', 'id')->where('business_branch_id', $branchId),
            ],
            'items.*.counted_quantity' => ['required_with:items', 'integer', 'min:0'],
            'items.*.adjustment_quantity' => ['nullable', 'integer'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
