<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionPaymentRequest;
use App\Http\Requests\UpdateSubscriptionPaymentRequest;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Auth;

class SubscriptionPaymentController extends Controller
{
    public function index()
    {
        $payments = SubscriptionPayment::with(['subscription.plan', 'paymentMethod', 'verifiedBy'])->get();
        return response()->json(["subscription_payments" => $payments, "message" => "Subscription payments retrieved"]);
    }

    public function store(StoreSubscriptionPaymentRequest $request)
    {
        $validated = $request->validated();

        // Default payment_status to pending when not provided
        if (!isset($validated['payment_status'])) {
            $validated['payment_status'] = 'pending';
        }

        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
        abort_if($paymentMethod->status !== 'enabled', 422, 'Payment method is not enabled');

        $payment = SubscriptionPayment::create($validated);
        return response()->json([
            'subscription_payment' => $payment->load(['subscription.plan', 'paymentMethod', 'verifiedBy']),
            'message' => 'Subscription payment created'
        ], 201);
    }

    public function show(SubscriptionPayment $subscriptionPayment)
    {
        return response()->json([
            'subscription_payment' => $subscriptionPayment->load(['subscription.plan', 'paymentMethod', 'verifiedBy']),
            'message' => 'Subscription payment retrieved'
        ]);
    }

    public function update(UpdateSubscriptionPaymentRequest $request, SubscriptionPayment $subscriptionPayment)
    {
        $validated = $request->validated();

        if (isset($validated['payment_status']) && $validated['payment_status'] === 'completed') {
            $validated['verified_by'] = Auth::id();
            $validated['verified_at'] = now();
        }

        if (isset($validated['payment_status']) && $validated['payment_status'] === 'rejected' && empty($validated['rejection_reason'])) {
            abort(422, 'Rejection reason is required when rejecting a payment');
        }

        $subscriptionPayment->update($validated);
        return response()->json([
            'subscription_payment' => $subscriptionPayment->fresh()->load(['subscription.plan', 'paymentMethod', 'verifiedBy']),
            'message' => 'Subscription payment updated'
        ]);
    }

    public function destroy(SubscriptionPayment $subscriptionPayment)
    {
        $subscriptionPayment->delete();
        return response()->json(['message' => 'Subscription payment deleted']);
    }
}
