<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Support\Auth\RolePermissions;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return RolePermissions::isElevated(Auth::user());
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['business_id' => Auth::user()->business_id]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_id' => ['nullable', 'integer', 'exists:businesses,id'],
        ];
    }
}
