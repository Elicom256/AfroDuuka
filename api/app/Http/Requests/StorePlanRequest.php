<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Authorization lives in PlanPolicy, not here.
 *
 * Plans have no business_id, so this request has no tenant to check against and
 * no meaningful "owns this record" relationship — the only question is whether
 * the caller is allowed to write platform-wide pricing at all. Returning true
 * here means "let the controller reach PlanPolicy"; the plan create/update/delete
 * authorization is enforced there via $this->authorize().
 */
class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug',
            // plans.mark is a NOT NULL enum with no default, and PlanSeeder supplies one
            // of these four. Without it here the insert reached Postgres with a null and
            // every POST /api/plans returned a 500 instead of creating a plan.
            'mark' => ['required', Rule::in(['Affordable', 'Most Popular', 'Best Value', 'Enterprise'])],
            'description' => 'nullable|string',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|max:50',
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
