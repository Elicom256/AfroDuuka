<?php

namespace App\Http\Requests;

use App\Models\BusinessTaxes;
use App\Models\CoreSettings\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreBusinessTaxPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Example: return $this->user()->can('create', BusinessTaxPayment::class);
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_branch_id' => ['required', 'exists:business_branches,id'],
            'business_tax_id'    => ['required', 'exists:business_taxes,id'],

            'amount'             => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'paid_amount'        => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],

            'tax_period'         => ['required', 'string', 'max:20'], // e.g., 2026-Q2, FY2026
            'due_date'           => ['required', 'date', 'after_or_equal:today'],

            'payment_date'       => ['nullable', 'date'],
            'paid_at'            => ['nullable', 'date_format:Y-m-d H:i:s'],

            'status'             => ['nullable', Rule::in(['unpaid', 'partial', 'paid', 'overdue', 'waived', 'refunded'])],

            'reference_number'   => ['nullable', 'string', 'max:50', 'unique:business_tax_payments,reference_number'],
            'payment_status_id'     => ['nullable', 'exists:payment_methods,id'],

            'notes'              => ['nullable', 'string', 'max:1000'],
            'payment_metadata'   => ['nullable', 'array'],

            'created_by'         => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'business_branch_id.exists' => 'The selected business branch does not exist.',
            'business_tax_id.exists'    => 'The selected tax type does not exist.',
            'amount.min'                => 'Amount must be at least 0.',
            'due_date.after_or_equal'   => 'Due date cannot be in the past.',
            'reference_number.unique'   => 'This reference number is already used.',
        ];
    }

    /**
     * Prepare data before validation (optional but useful)
     */
    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $rate = BusinessTaxes::where('id', $this->business_tax_id)->value('rate');
        $paid_amount =  ($rate / 100) * $this->amount;
        $this->merge([
            'business_branch_id' => $user->business_branch_id,
            'paid_amount' => $paid_amount ?? 0,
            // 'status'      => $this->status ?? 'unpaid',
            "created_by" => Auth::user()->id,
            'payment_date' => Carbon::now(),
        ]);
    }
}