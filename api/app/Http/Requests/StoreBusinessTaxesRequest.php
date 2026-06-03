<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBusinessTaxesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    
protected function prepareForValidation():void{
    $user = Auth::user();
            $this->merge([
                "business_id" => $user->business_id,
                //  "business_branch_id" => $user->business_branch_id,
                 "status" => "active"
            ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_id' => ['nullable', 'exists:businesses,id'],
            // 'business_branch_id' => ['nullable', 'exists:business_branches,id'],
            'name' => ['required', 'string', 'max:150'],
            'rate' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive,pending'],
        ];
    }
}
