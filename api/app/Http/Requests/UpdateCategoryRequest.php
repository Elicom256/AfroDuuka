<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    // prepare for validation
    public function prepareForValidation(){
        $user = Auth::user();
        if (!$this->status) {
            $this->merge([
            "business_id" => $user->business_id,
            "status" => true
        ]);
        }else{
            $this->merge([
            "business_id" => $user->business_id,
        ]);
        }

    }


     // * Get the validation rules that apply to the request.
    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|min:1|max:255',
            'description' => 'required|string|min:1|max:255',
            'status' => 'required|boolean',
        ];
    }
}
