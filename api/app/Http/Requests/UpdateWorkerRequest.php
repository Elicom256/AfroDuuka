<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Override;

class UpdateWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::user();

        $this->merge([
            "business_id"        => $user->business_id,
            "business_branch_id" => $user->business_branch_id,
        ]);
    }

     protected function failedValidation(Validator $validator)
     {
        return dd($validator);
     }

    public function rules(): array
    {
        // Adjust route param names to match your routes
        $workerId = $this->route("worker"); 
        $userId   = $this->route("user");

        return [
            
            'department'      => 'nullable|string|max:255',
            'position'        => 'nullable|string|max:255',
            'employment_type' => ['nullable', Rule::in(['full_time','part_time','contract','intern'])],
            'salary'          => 'nullable|numeric|min:0',
            'hire_date'       => 'nullable|date',
            'remarks'         => 'nullable|string',

            'firstname' => 'sometimes|nullable|string|max:255',
            'lastname'  => 'sometimes|nullable|string|max:255',
            'email'     => [
                'required','email',
                Rule::unique('users','email')->ignore($userId),
            ],
            'password'  => 'nullable|string|min:6',
            'username'  => [
                'nullable','string','max:255',
                Rule::unique('users','username')->ignore($userId),
            ],
            'phone'     => [
                'nullable','string','digits:10',
                Rule::unique('users','phone')->ignore($userId),
            ],
            'nin'       => [
                'nullable','string',
                Rule::unique('users','nin')->ignore($userId),
            ],
            'address'   => 'nullable|string|max:255',
            'status'             => 'required|in:active,inactive',
        ];
    }
}
