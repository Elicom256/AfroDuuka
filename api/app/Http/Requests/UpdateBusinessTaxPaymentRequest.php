<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateBusinessTaxPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return $this->user()->can('update', $this->businessTaxPayment);
        return Auth::check();
    }

protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $this->merge([
            "business_id" => $user->business_id,
            'updated_by' => $user->id,
        ]);

        // Only set business_branch_id if not already provided in the request
        if (!$this->has('business_branch_id')) {
            $this->merge(['business_branch_id' => $user->business_branch_id]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $paymentId = $this->route('business_tax_payment'); // assuming route model binding

        return [
            'business_branch_id' => ['sometimes', 'required', 'exists:business_branches,id'],
            // 'business_tax_id'    => ['sometimes', 'required', 'exists:business_taxes,id'],

            'amount'             => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999.99'],
            'paid_amount'        => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],

            'tax_period'         => ['sometimes', 'required', 'string', 'max:20'],
            'due_date'           => ['sometimes', 'required', 'date'],

            'payment_date'       => ['nullable', 'date'],
            'paid_at'            => ['nullable', 'date_format:Y-m-d H:i:s'],

            'status'             => ['sometimes', Rule::in(['unpaid', 'partial', 'paid', 'overdue', 'waived', 'refunded'])],

            'reference_number'   => [
                'nullable', 
                'string', 
                'max:50', 
                Rule::unique('business_tax_payments', 'reference_number')->ignore($paymentId)
            ],

            'payment_status_id'     => ['nullable',  'exists:payment_statuses,id'],

            'notes'              => ['nullable', 'string', 'max:1000'],
            'payment_metadata'   => ['nullable', 'array'],

            'updated_by'         => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'business_branch_id.exists' => 'The selected business branch does not exist.',
            'business_tax_id.exists'    => 'The selected tax type does not exist.',
            'amount.min'                => 'Amount must be at least 0.',
            'reference_number.unique'   => 'This reference number is already used by another payment.',
        ];
    }

    /**
     * Prepare the data before validation
     */

}