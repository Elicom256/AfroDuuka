<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(["plan.pricing", "plan.business", "paymentMethod"])->get();
        return response()->json(["subscriptions" => $subscriptions, "message" => "Subscriptions retrieved"]);
    }

    public function store(StoreSubscriptionRequest $request)
    {
        $validated = $request->validated();

        $paymentMethod = PaymentMethod::findOrFail($validated["payment_method_id"]);
        abort_if($paymentMethod->status !== "enabled", 422, "Payment method is not enabled");

        $subscription = Subscription::create($validated);
        return response()->json([
            "subscription" => $subscription->load(["plan.pricing", "plan.business", "paymentMethod"]),
            "message" => "Subscription created"
        ], 201);
    }

    public function show(Subscription $subscription)
    {
        return response()->json([
            "subscription" => $subscription->load(["plan.pricing", "plan.business", "paymentMethod"]),
            "message" => "Subscription retrieved"
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();
        $subscription->update($validated);
        return response()->json([
            "subscription" => $subscription->fresh()->load(["plan.pricing", "plan.business", "paymentMethod"]),
            "message" => "Subscription updated"
        ]);
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return response()->json(["message" => "Subscription deleted"]);
    }
}
