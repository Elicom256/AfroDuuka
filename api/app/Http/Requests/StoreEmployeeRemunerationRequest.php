<?php

namespace App\Http\Requests;

use App\Models\Worker;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreEmployeeRemunerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $worker = Worker::with('user')->find($this->input('worker_id'));

        $this->merge([
            'business_id' => $user->business_id,
            'business_branch_id' => $worker?->business_branch_id ?? $user->business_branch_id,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'exists:workers,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', 'max:80'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:pending,paid,failed'],
            'description' => ['nullable', 'string'],
            'business_id' => ['required', 'exists:businesses,id'],
            'business_branch_id' => ['required', 'exists:business_branches,id'],
        ];
    }
}
