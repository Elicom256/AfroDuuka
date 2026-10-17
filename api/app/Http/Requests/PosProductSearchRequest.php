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
        $query = $this->input('q');
        $isEmpty = $query === null || trim((string) $query) === '';

        // An empty query is the POS catalog warm-up: the client caches the whole branch
        // catalog in IndexedDB so search and barcode scan still work offline. It must be
        // allowed through, and PosService::searchProducts answers it with the catalog.
        if ($isEmpty) {
            return [
                'q' => 'nullable|string|max:255',
            ];
        }

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
