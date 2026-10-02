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
            // The onboarding form asks for a business email and a business phone, and
            // both were missing here. FormRequest::validated() returns only declared
            // rules, so the two values the user typed were dropped before the service
            // ever saw them and the business silently inherited the owner's contact
            // details instead. They are optional so that the one-screen signup, which
            // does not collect them, keeps working.
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|min:7|max:20',
            // The branch list the onboarding form collected, created in the same request
            // as the business. See BusinessService::create() for why this is one call
            // rather than a follow-up per branch.
            'branches' => 'sometimes|array|max:20',
            'branches.*.name' => 'required_with:branches|string|max:255',
            'branches.*.address' => 'nullable|string|max:255',
            'branches.*.phone' => 'nullable|string|min:7|max:20',
        ];
    }

    /**
     * Messages for the rules whose Laravel default reads as machine output.
     */
    public function messages(): array
    {
        return [
            'branches.*.name.required_with' => 'Every branch needs a name.',
            'phone.min' => 'The phone number looks too short.',
            'phone.max' => 'The phone number looks too long.',
        ];
    }

    /**
     * Reject a payload that lists the same branch twice.
     *
     * business_branches has a unique (business_id, name) index, so two branches called
     * "Main Branch" abort the whole request with a raw SQLSTATE 23505 after the business
     * row is already written. Caught here it is a message the owner can act on, and
     * because the service writes inside a transaction nothing is persisted either way.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $branches = $this->input('branches');

            if (! is_array($branches)) {
                return;
            }

            $seen = [];

            foreach ($branches as $index => $branch) {
                $name = is_array($branch) ? trim((string) ($branch['name'] ?? '')) : '';

                if ($name === '') {
                    continue;
                }

                $key = mb_strtolower($name);

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "branches.$index.name",
                        'Branch names must be different — "'.$name.'" is listed twice.'
                    );
                }

                $seen[$key] = true;
            }
        });
    }
}