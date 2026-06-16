<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdatePrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', 'string', Rule::in(['bluetooth', 'usb', 'network'])],
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
