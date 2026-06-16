<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateReorderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'reorder_quantity' => 'sometimes|integer|min:1',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
            'auto_approve' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
