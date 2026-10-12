<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PosProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            // min:2 because the query drives a LIKE against every product in the
            // caller's branch, and a single character matches most of them — the
            // barcode and name columns both prefix-match on the first character. The
            // till then renders and transfers the whole result set on a keystroke that
            // was never meant to search.
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
