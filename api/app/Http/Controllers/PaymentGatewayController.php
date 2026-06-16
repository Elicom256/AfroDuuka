<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentGatewayRequest;
use App\Http\Requests\UpdatePaymentGatewayRequest;
use App\Models\PaymentGateway;

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

    public function store(StorePaymentGatewayRequest $request)
    {
        $gateway = PaymentGateway::create($request->validated());
        return response()->json(['message' => 'Payment gateway created', 'data' => $gateway], 201);
    }

    public function show(PaymentGateway $paymentGateway)
    {
        return response()->json(['message' => 'Fetched payment gateway', 'data' => $paymentGateway]);
    }

    public function update(UpdatePaymentGatewayRequest $request, PaymentGateway $paymentGateway)
    {
        $paymentGateway->update($request->validated());
        return response()->json(['message' => 'Payment gateway updated', 'data' => $paymentGateway]);
    }

    public function destroy(PaymentGateway $paymentGateway)
    {
        $paymentGateway->delete();
        return response()->json(['message' => 'Payment gateway deleted']);
    }
}
