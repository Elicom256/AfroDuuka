<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:draft,in_transit,received,cancelled',
            'notes' => 'nullable|string|max:1000',
            'transport_cost' => 'nullable|numeric|min:0',
        ];
    }
}
