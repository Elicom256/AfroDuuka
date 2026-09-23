<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $branchId = Auth::user()?->business_branch_id;

        return [
            'tax_category_id' => [
                'required',
                'integer',
                Rule::exists('tax_categories', 'id')->where('business_branch_id', $branchId),
            ],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'rate' => ['required', 'numeric', 'between:0,1'],
            'jurisdiction_zone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}