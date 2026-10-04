<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionPaymentRequest;
use App\Http\Requests\UpdateSubscriptionPaymentRequest;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Support\Auth\RolePermissions;
use Illuminate\Support\Facades\Auth;

class SubscriptionPaymentController extends Controller
{
    /**
     * A subscription payment is billing, so it follows canManageSubscriptions.
     *
     * store() already refused to record a payment against another business, but that
     * check is a tenant boundary, not a role boundary: any Operations account inside
     * its own business could file and then mark payments completed, and completing one
     * credits subscription_balance straight onto the business. update() had no gate at
     * all beyond validation, which is the write that moves money.
     */
    public function index()
    {
        $payments = SubscriptionPayment::with(['subscription.plan', 'subscription.business', 'paymentMethod', 'verifiedBy'])->get();

        return response()->json(['subscription_payments' => $payments, 'message' => 'Subscription payments retrieved']);
    }

    public function store(StoreSubscriptionPaymentRequest $request)
    {
        abort_unless(RolePermissions::canManageSubscriptions(Auth::user()), 403, 'You cannot record subscription payments.');

        $validated = $request->validated();

        // Default payment_status to pending when not provided
        if (! isset($validated['payment_status'])) {
            $validated['payment_status'] = 'pending';
        }

        $subscription = Subscription::findOrFail($validated['subscription_id']);

        $user = Auth::user();
        if ($user->business_id && $subscription->business_id !== $user->business_id) {
            abort(403, 'Cannot record payment for another business.');
        }

        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
        abort_if($paymentMethod->status !== 'enabled', 422, 'Payment method is not enabled');

        $payment = SubscriptionPayment::create($validated);

        return response()->json([
            'subscription_payment' => $payment->load(['subscription.plan', 'paymentMethod', 'verifiedBy']),
            'message' => 'Subscription payment created',
        ], 201);
    }

    public function show(SubscriptionPayment $subscriptionPayment)
    {
        abort_unless(RolePermissions::canManageSubscriptions(Auth::user()), 403, 'You cannot view subscription payments.');

        return response()->json([
            'subscription_payment' => $subscriptionPayment->load(['subscription.plan', 'paymentMethod', 'verifiedBy']),
            'message' => 'Subscription payment retrieved',
        ]);
    }

    public function update(UpdateSubscriptionPaymentRequest $request, SubscriptionPayment $subscriptionPayment)
    {
        // Marking a payment 'completed' credits subscription_balance below, so this is
        // the single most consequential write in the module and the gate sits in front
        // of it rather than only around the tenant check.
        abort_unless(RolePermissions::canManageSubscriptions(Auth::user()), 403, 'You cannot update subscription payments.');

        $subscription = $subscriptionPayment->subscription;

        if ($subscription && Auth::user()->business_id !== null
            && $subscription->business_id !== Auth::user()->business_id) {
            abort(403, 'Cannot update payment for another business.');
        }

        $validated = $request->validated();

        if (isset($validated['payment_status']) && $validated['payment_status'] === 'rejected' && empty($validated['rejection_reason'])) {
            abort(422, 'Rejection reason is required when rejecting a payment');
        }

        // Only credit on the transition into completed. Re-sending 'completed' for a
        // payment that already carries it used to increment subscription_balance again,
        // so a double-clicked verify button credited the business twice for one
        // payment. $subscriptionPayment is the pre-update row, so its status is the
        // state being transitioned from.
        if (
            ($validated['payment_status'] ?? null) === 'completed'
            && $subscriptionPayment->payment_status !== 'completed'
        ) {
            $validated['verified_by'] = Auth::id();
            $validated['verified_at'] = now();

            if ($subscription && $subscription->business) {
                $subscription->business->increment('subscription_balance', $subscriptionPayment->amount_paid);
            }
        }

        $subscriptionPayment->update($validated);

        return response()->json([
            'subscription_payment' => $subscriptionPayment->fresh()->load(['subscription.plan', 'paymentMethod', 'verifiedBy']),
            'message' => 'Subscription payment updated',
        ]);
    }

    public function destroy(SubscriptionPayment $subscriptionPayment)
    {
        abort_unless(RolePermissions::canManageSubscriptions(Auth::user()), 403, 'You cannot delete subscription payments.');

        $subscription = $subscriptionPayment->subscription;

        if ($subscription && Auth::user()->business_id !== null
            && $subscription->business_id !== Auth::user()->business_id) {
            abort(403, 'Cannot delete payment for another business.');
        }

        $subscriptionPayment->delete();

        return response()->json(['message' => 'Subscription payment deleted']);
    }
}
