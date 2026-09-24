<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBusinessCreditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return Auth::check();
    }

 public function rules(): array
{
    return [
        'customer_id' => 'sometimes|exists:customers,id',
        'amount' => 'sometimes|numeric|min:0.01',
        'reference' => 'sometimes|nullable|string|max:50',
        'status' => 'sometimes|in:open,settled',
        'description' => 'sometimes|nullable|string|max:255',
    ];
}

    public function messages(): array
    {
        return [
            'customer_id.required'        => 'Customer is required',
            'customer_id.exists'          => 'Customer must exist',
            'amount.required'             => 'Amount is required',
            'amount.numeric'              => 'Amount must be numeric',
            'amount.min'                  => 'Amount must be greater than zero',
            'status.required'             => 'Status is required',
            'status.in'                   => 'Status must be open or settled',
            'reference.max'               => 'Reference may not exceed 50 characters',
            'description.max'             => 'Description may not exceed 255 characters',
        ];
    }
}
