<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Authorization lives in PlanPolicy — see StorePlanRequest for why a plan request
 * cannot authorize on its own. The update path also re-validates the slug against
 * the row being edited via $this->route('plan').
 */
class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:plans,slug,'.$this->route('plan'),
            // Same enum as create. It stays out of $fillable's reach on its own, so an
            // unvalidated "mark" here would be dropped silently rather than rejected.
            'mark' => ['sometimes', Rule::in(['Affordable', 'Most Popular', 'Best Value', 'Enterprise'])],
            'description' => 'nullable|string',
            'monthly_price' => 'sometimes|numeric|min:0',
            'yearly_price' => 'sometimes|numeric|min:0',
            'billing_cycle' => 'sometimes|string|max:50',
            'discount_percentage' => 'sometimes|integer|min:0|max:100',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'currency' => 'string|max:10',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
