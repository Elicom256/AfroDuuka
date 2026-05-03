<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
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
    // protected function prepareForValidation():void{
    //     if(!$this->has("email") || empty($this->input("email"))){
    //         $this->merge([
    //             "email" => auth()->user()->email
    //         ]);
    //     }
    // }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // 'email' => 'required|email|unique:businesses,email',
            'business_category_id' => 'required|exists:business_categories,id',
        ];
    }
}
