<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::user();
        $business = $user->business()->with('country')->first();
        $defaultCurrency = $business?->country?->currency_code ?? 'UGX';

        $this->merge([
            'business_branch_id' => $this->input('business_branch_id', $user->business_branch_id),
            'currency' => $this->input('currency', $defaultCurrency),
        ]);
    }

    public function rules(): array
    {
        $branchWithinSet = function ($attribute, $value, $fail) {
            $resolved = EffectiveBranchScope::validationBranchesFor(Auth::user());
            if ($resolved !== null && ! in_array((int) $value, $resolved[1], true)) {
                $fail('The selected business branch is outside your scope.');
            }
        };

        return [
            'business_branch_id' => ['required', 'integer', 'exists:business_branches,id', $branchWithinSet],

            'customer_id' => 'nullable|exists:customers,id',

            'sale_id' => 'nullable|exists:sales,id',

            'items' => 'required_without:sale_id|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',

            'payments' => 'required|array|min:1',
            'payments.*.method' => ['required', 'string', Rule::in(['cash', 'mobile_money', 'card', 'credit'])],
            // gt:0 rather than min:0. A zero-value payment row is not a real tender —
            // it is a placeholder that adds a 'paid' SalePayment for nothing while
            // inflating the count of payment methods on the receipt.
            'payments.*.amount' => 'required|numeric|gt:0',

            'note' => 'nullable|string|max:500',
            'currency' => 'required|string|size:3',
        ];
    }

    /**
     * The payments have to cover the cart.
     *
     * Nothing checked this anywhere. PosService::checkout() trusted the submitted
     * amounts, summed them into $totalPaid, and carried on: a client could POST a full
     * cart with payments totalling less than the sale and the sale was still created as
     * 'completed', its payment rows recorded as 'paid', and the receipt showed the
     * shortfall as zero change rather than as money still owed. The goods had already
     * left the counter by then, and the cash-flow row recorded the full amount as
     * received — so the books showed revenue that had not come in.
     *
     * This is checked here rather than in the service because the service computes the
     * total from tax rules and discounts, and by the time it knows the total it has
     * already created the sale and decremented stock. The tendered sum is knowable from
     * the payload alone, so the two are compared before anything is written.
     *
     * Credit is a legitimate tender and is included: the charge is recorded against the
     * customer in the service, so excluding it would break the store-credit flow rather
     * than protect revenue.
     *
     * Note this cannot see a held sale resumed by sale_id — that total comes from the
     * stored row, not the payload. PosService::checkout() re-checks it against the
     * resolved total for that path.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('sale_id')) {
                return;
            }

            $total = 0.0;

            foreach ((array) $this->input('items', []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $quantity = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);

                // Mirrors the service's own arithmetic minus tax, which is the one
                // component the payload cannot supply. This check is therefore a
                // lower bound: it catches the ordinary underpayment, and the service
                // re-checks against the tax-inclusive total.
                $total += max(0, ($price * $quantity) - ($discount * $quantity));
            }

            $tendered = 0.0;
            foreach ((array) $this->input('payments', []) as $payment) {
                if (is_array($payment)) {
                    $tendered += (float) ($payment['amount'] ?? 0);
                }
            }

            if ($tendered < $total) {
                $validator->errors()->add(
                    'payments',
                    sprintf(
                        'Payments total %.2f but the cart is %.2f. Short by %.2f.',
                        $tendered,
                        $total,
                        $total - $tendered
                    )
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product to the cart.',
            'items.min' => 'Add at least one product to the cart.',
            'payments.required' => 'At least one payment method is required.',
            'payments.min' => 'At least one payment method is required.',
        ];
    }
}
