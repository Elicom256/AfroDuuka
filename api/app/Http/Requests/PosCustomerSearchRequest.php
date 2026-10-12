<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PosCustomerSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            // min:2 for the same reason as PosProductSearchRequest: the query is a
            // LIKE over the customer list and a single character matches most names
            // and phone numbers in it.
            'q' => 'required|string|min:2|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'q.min' => 'Type at least two characters to search.',
        ];
    }
}
