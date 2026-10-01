<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBusinessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
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
            'address' => 'nullable|string|min:1|max:255',
            'business_category_id' => 'required|exists:business_categories,id',
            // Required, because businesses.country_id is a foreignId()->constrained()
            // column and therefore NOT NULL. It was previously optional with the service
            // silently defaulting to Uganda, which both wrote the wrong country onto
            // businesses that never chose one and 500'd outright when the countries table
            // had no rows to default to. A 422 is the correct failure for a missing
            // country.
            'country_id' => 'required|exists:countries,id',
        ];
    }
}
