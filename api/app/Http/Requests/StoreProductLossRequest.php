<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductLossRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:damaged,expired,lost',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
            'loss_date' => 'nullable|date',
        ];
    }
}
