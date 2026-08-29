<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['plan', 'business', 'payments'])->get();
        return response()->json(["subscriptions" => $subscriptions, "message" => "Subscriptions retrieved"]);
    }

    public function store(StoreSubscriptionRequest $request)
    {
        $validated = $request->validated();
        $businessId = $validated['business_id'];

        // Enforce one active subscription per business
        Subscription::where('business_id', $businessId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $subscription = Subscription::create($validated);
        $plan = Plan::find($subscription->plan_id);

        (new WhatsAppNotificationService())->queueBusinessNotification([
            'business_id' => $subscription->business_id,
            'type' => 'subscription.created',
            'template_key' => 'subscription.created',
            'recipient_phone' => $subscription->business?->phone ?? Auth::user()?->phone ?? '+256731794401',
            'template_data' => [
                'business_name' => $subscription->business?->name ?? 'Your business',
                'plan_name' => $plan?->name ?? 'Your plan',
                'expiry_date' => $subscription->ends_at?->format('Y-m-d') ?? now()->addMonth()->format('Y-m-d'),
            ],
        ]);

        return response()->json([
            "subscription" => $subscription->load(['plan', 'business', 'payments']),
            "message" => "Subscribed to $plan->name!"
        ], 201);
    }

    public function show(Subscription $subscription)
    {
        return response()->json([
            "subscription" => $subscription->load(['plan', 'business', 'payments']),
            "message" => "Subscription retrieved"
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();
        $subscription->update($validated);
        return response()->json([
            "subscription" => $subscription->fresh()->load(['plan', 'business', 'payments']),
            "message" => "Subscription updated"
        ]);
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return response()->json(["message" => "Subscription deleted"]);
    }
}
