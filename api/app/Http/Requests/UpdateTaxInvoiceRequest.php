<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateTaxInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'ura_qr_code' => 'nullable|string',
            'submitted_to_ura' => 'boolean',
            'status' => 'sometimes|string|in:draft,submitted,cancelled',
        ];
    }
}
