<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorization lives in SubscriptionPolicy and runs from SubscriptionController.
 *
 * business_id is deliberately absent from the rules. It used to be updatable, which
 * meant a PUT could re-point a subscription at a different business: the policy
 * checked the row's *current* tenant, the request then moved the row, and the tenant
 * scope would find it in the wrong place afterwards. The tenant of a subscription is
 * set on create (StoreSubscriptionRequest pins it to the caller) and does not move.
 */
class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'sometimes|exists:plans,id',
            // Rejected outright rather than ignored. Dropping the key silently would
            // answer 200 to a caller asking to move a subscription to another
            // business, and they would reasonably believe it had worked.
            'business_id' => 'prohibited',
            'status' => 'sometimes|in:active,paused,cancelled,expired',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'trial_ends_at' => 'nullable|date',
        ];
    }
}
