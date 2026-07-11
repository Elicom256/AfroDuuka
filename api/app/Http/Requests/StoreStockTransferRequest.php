<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            'transferred_by' => Auth::id(),
            'status' => $this->input('status', 'draft'),
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'from_branch_id' => 'required|exists:business_branches,id|different:to_branch_id',
            'to_branch_id' => 'required|exists:business_branches,id',
            'status' => 'sometimes|string|in:draft,in_transit,received,cancelled',
            'transferred_by' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'transport_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_expected' => 'required|integer|min:1',
        ];
    }
}
