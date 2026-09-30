<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $logName = trim((string) $this->input('log_name'));

        // The UI sends "all" as a sentinel for "no category filter".
        $this->merge([
            'log_name' => strtolower($logName) === 'all' ? null : ($logName ?: null),
            'per_page' => $this->input('per_page') ?: 20,
        ]);
    }

    public function rules(): array
    {
        return [
            'log_name' => [
                'nullable',
                'string',
                'max:100',
                Rule::notIn(['all']),
            ],
            'causer_id' => 'nullable|integer|exists:users,id',
            'subject_type' => 'nullable|string|max:191',
            'subject_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'search' => 'nullable|string|max:191',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'log_name.not_in' => 'The selected category filter is not valid.',
        ];
    }
}
