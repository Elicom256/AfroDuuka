<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\CoreSettings\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::where("status", "enabled")->get();
        $setting = PaymentMethod::all();
        return response()->json(["settings" =>$setting, "message" => "Payments settings", "methods" => $methods]);
    }

    public function store(StorePaymentMethodRequest $request)
    {
        //
    }

    public function show(PaymentMethod $paymentMethod)
    {
        abort_if($paymentMethod->status !== "enabled", 404);
         return response()->json(["methods" => $paymentMethod, "message" => "Payment method fetched", ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
         $validated = $request->validated();
        $setting = $paymentMethod->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        //
    }
}
