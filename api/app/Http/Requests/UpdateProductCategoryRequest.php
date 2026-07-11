<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function prepareForValidation()
    {
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
            'description' => 'required|string|min:1|max:255',
            'status' => 'required|boolean',
        ];
    }
}
