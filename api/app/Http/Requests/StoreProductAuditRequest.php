<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'business_branch_id' => ['required', 'exists:business_branches,id'],
            'audit_date' => ['required', 'date'],
            'status' => ['nullable', 'in:draft,in_progress,completed'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.counted_quantity' => ['required', 'integer', 'min:0'],
            'items.*.adjustment_quantity' => ['nullable', 'integer'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
