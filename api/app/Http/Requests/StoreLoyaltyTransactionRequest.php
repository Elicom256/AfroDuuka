<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreLoyaltyTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'loyalty_card_id' => 'required|exists:loyalty_cards,id',
            'sale_id' => 'nullable|exists:sales,id',
            'type' => ['required', 'string', Rule::in(['earn', 'burn', 'adjust'])],
            'points' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
        ];
    }
}
