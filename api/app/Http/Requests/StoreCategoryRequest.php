<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreCategoryRequest extends FormRequest
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
            "status" => "active"
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|min:1|max:255',
            'description' => 'required|string|min:1|max:255',
            'status' => 'required|in:active,inactive',
        ];
    }
}
