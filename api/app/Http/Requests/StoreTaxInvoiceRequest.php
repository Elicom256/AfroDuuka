<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTaxInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => Auth::user()->business_id,
            'business_branch_id' => Auth::user()->business_branch_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'business_branch_id' => 'required|exists:business_branches,id',
            'sale_id' => 'required|exists:sales,id',
            'invoice_number' => 'required|string|unique:tax_invoices,invoice_number',
            'vat_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'sometimes|string|in:draft,submitted,cancelled',
        ];
    }
}
