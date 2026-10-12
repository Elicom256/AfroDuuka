<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentGatewayRequest;
use App\Http\Requests\UpdatePaymentGatewayRequest;
use App\Models\PaymentGateway;
use App\Support\Auth\RolePermissions;
use Illuminate\Support\Facades\Auth;

/**
 * Manages payment provider credentials (MTN MoMo, Airtel Money, etc.).
 */
class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::where('business_id', auth()->user()->business_id)->get();

        return response()->json(['message' => 'Fetched payment gateways', 'data' => $gateways]);
    }

    /**
     * Reads stay open to any signed-in account of the business: the till has to
     * resolve which provider a sale is settling through mid-transaction.
     */
    public function store(StorePaymentGatewayRequest $request)
    {
        abort_unless(RolePermissions::canManagePaymentConfig($request->user()), 403, 'You cannot manage payment gateway credentials.');

        $gateway = PaymentGateway::create($request->validated());

        return response()->json(['message' => 'Payment gateway created', 'data' => $gateway], 201);
    }

    public function show(PaymentGateway $paymentGateway)
    {
        return response()->json(['message' => 'Fetched payment gateway', 'data' => $paymentGateway]);
    }

    /**
     * This row is where live mobile-money payments are routed and how an inbound
     * webhook is authenticated, so it is held to canManagePaymentConfig() rather
     * than to authentication. StorePaymentGatewayRequest::authorize() answers
     * Auth::check(), which is who you are and not what you may do.
     */
    public function update(UpdatePaymentGatewayRequest $request, PaymentGateway $paymentGateway)
    {
        abort_unless(RolePermissions::canManagePaymentConfig($request->user()), 403, 'You cannot manage payment gateway credentials.');

        $paymentGateway->update($request->validated());

        return response()->json(['message' => 'Payment gateway updated', 'data' => $paymentGateway]);
    }

    public function destroy(PaymentGateway $paymentGateway)
    {
        // Deleting the row is strictly more dangerous than writing it: it removes the
        // credentials every live mobile-money payment and webhook check routes
        // through. store/update already hold canManagePaymentConfig(); delete
        // answered authentication only, so any signed-in account could pull the
        // plumbing out from under live transactions.
        abort_unless(RolePermissions::canManagePaymentConfig(request()->user()), 403, 'You cannot manage payment gateway credentials.');

        $paymentGateway->delete();

        return response()->json(['message' => 'Payment gateway deleted']);
    }
}
