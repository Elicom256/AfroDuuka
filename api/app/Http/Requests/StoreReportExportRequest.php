<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            'user_id' => Auth::id(),
            'status' => 'pending',
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'user_id' => 'required|exists:users,id',
            'report_type' => 'required|string|max:100',
            'format' => ['required', 'string', Rule::in(['csv', 'xlsx', 'pdf'])],
            'parameters' => 'nullable|json',
            'status' => 'sometimes|string|in:pending,processing,completed,failed',
        ];
    }
}
