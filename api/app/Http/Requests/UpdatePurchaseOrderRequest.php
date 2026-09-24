<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pending,approved,cancelled',
            'notes' => 'nullable|string|max:500',
        ];
    }
}