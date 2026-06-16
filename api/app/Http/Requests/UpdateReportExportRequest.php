<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'file_path' => 'nullable|string',
            'status' => ['sometimes', 'string', Rule::in(['pending', 'processing', 'completed', 'failed'])],
            'generated_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ];
    }
}
