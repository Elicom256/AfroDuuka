<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            'business_branch_id' => Auth::user()->business_branch_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'business_branch_id' => 'required|exists:business_branches,id',
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(['bluetooth', 'usb', 'network'])],
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
