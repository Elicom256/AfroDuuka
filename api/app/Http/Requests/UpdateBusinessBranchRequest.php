<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBusinessBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation(){
        $user = Auth::user();
        $this->merge([
            "business_id" => $user->business_id,
        ]);
    }
    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|min:1|max:255',
            'address' => 'nullable|string|min:1|max:255',
            'phone' => 'nullable|string|digits_between:10,10',
            'status' => 'required|string|in:active,innactive',
        ];
    }
}
