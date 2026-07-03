<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Subscription;

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
        $subscription = Subscription::create($validated);
        return response()->json([
            "subscription" => $subscription->load(['plan', 'business', 'payments']),
            "message" => "Subscription created"
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
