<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Support\Auth\RolePermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Subscriptions are business-level billing.
 *
 * The route group carries no 'role' middleware and every request below validated
 * with a bare Auth::check(), so before this pass any signed-in account could
 * change the plan and expiry dates of its business, and — because store() cancels
 * the business's active subscription before creating a replacement — could strand
 * it on no plan at all.
 */
class SubscriptionController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Subscription::class);

        $subscriptions = Subscription::with(['plan', 'business', 'payments'])->get();

        return response()->json(['subscriptions' => $subscriptions, 'message' => 'Subscriptions retrieved']);
    }

    public function store(StoreSubscriptionRequest $request)
    {
        $this->authorize('create', Subscription::class);

        $validated = $request->validated();
        $businessId = $validated['business_id'];

        // Enforce one active subscription per business. The cancel + create pair must
        // be atomic: a failure between the two statements would otherwise leave the
        // business with no active subscription at all.
        $subscription = DB::transaction(function () use ($validated, $businessId) {
            Subscription::where('business_id', $businessId)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            return Subscription::create($validated);
        });

        $plan = Plan::find($subscription->plan_id);

        (new WhatsAppNotificationService)->queueBusinessNotification([
            'business_id' => $subscription->business_id,
            'type' => 'subscription.created',
            'template_key' => 'subscription.created',
            // No hardcoded fallback. A subscription created for a business with no phone
            // on file has nowhere to go, and the old `?? '+256731794401'` sent it to a
            // personal number that had nothing to do with the customer.
            'recipient_phone' => $subscription->business?->phone ?? Auth::user()?->phone,
            'template_data' => [
                'business_name' => $subscription->business?->name ?? 'Your business',
                'plan_name' => $plan?->name ?? 'Your plan',
                'expiry_date' => $subscription->ends_at?->format('Y-m-d') ?? now()->addMonth()->format('Y-m-d'),
            ],
        ]);

        return response()->json([
            'subscription' => $subscription->load(['plan', 'business', 'payments']),
            'message' => "Subscribed to $plan->name!",
        ], 201);
    }

    public function show(Subscription $subscription)
    {
        $this->authorize('view', $subscription);

        return response()->json([
            'subscription' => $subscription->load(['plan', 'business', 'payments']),
            'message' => 'Subscription retrieved',
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $this->authorize('update', $subscription);

        $validated = $request->validated();
        $subscription->update($validated);

        return response()->json([
            'subscription' => $subscription->fresh()->load(['plan', 'business', 'payments']),
            'message' => 'Subscription updated',
        ]);
    }

    public function destroy(Subscription $subscription)
    {
        $this->authorize('delete', $subscription);

        $subscription->delete();

        return response()->json(['message' => 'Subscription deleted']);
    }
}
