<?php

namespace App\Http\Requests;

use App\Support\Auth\RolePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductCategoryRequest extends FormRequest
{
    /**
     * Refused before validation, so a restricted role cannot tell a rejected category
     * from a rejected field. See the note in StoreProductRequest::authorize().
     */
    public function authorize(): bool
    {
        return Auth::check() && RolePermissions::canCreateCatalog($this->user());
    }

    public function prepareForValidation()
    {
        $user = Auth::user();
        $this->merge([
            'business_id' => $user->business_id,
            'status' => 'active',
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
