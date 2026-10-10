<?php

namespace App\Http\Requests;

use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreProductAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
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
            'audit_date' => ['required', 'date'],
            'status' => ['nullable', 'in:draft,in_progress,completed'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            // A product may only be counted against the branch the audit is for. A bare
            // `exists:products,id` passes a product from another branch and lets the
            // error surface later as a 404 out of ProductAuditService's scope-aware
            // findOrFail; pinning the branch here turns that into a 422 at the door.
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(
                    'business_branch_id',
                    (int) $this->input('business_branch_id')
                ),
            ],
            'items.*.counted_quantity' => ['required', 'integer', 'min:0'],
            'items.*.adjustment_quantity' => ['nullable', 'integer'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
