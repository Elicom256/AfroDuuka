<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class OpenCashDrawerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'business_branch_id' => 'nullable|integer|exists:business_branches,id',
            'opening_cash' => 'required|numeric|min:0',
            'allowed_variance' => 'nullable|numeric|min:0',
        ];
    }
}
