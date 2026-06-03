<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRemunerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'worker_id' => ['sometimes', 'required', 'exists:workers,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'required', 'string', 'max:80'],
            'payment_date' => ['sometimes', 'required', 'date'],
            'reference' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,paid,failed'],
        ];
    }
}
