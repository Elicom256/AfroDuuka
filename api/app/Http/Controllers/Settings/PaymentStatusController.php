<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentStatusRequest;
use App\Http\Requests\UpdatePaymentStatusRequest;
use App\Models\CoreSettings\PaymentStatus;

// use App\Models\PaymentStatus;

class PaymentStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $methods = PaymentStatus::where("status", "enabled")->get();
        $setting = PaymentStatus::all();
        return response()->json(["settings" =>$setting, "message" => "Payments settings", "methods" => $methods]);
    }

// public function allowed()
//     {
//         $methods = PaymentStatus::all();
//         return response()->json(["methods" =>$methods, "message" => "Allowed Payments settings"]);
//     }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentStatusRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentStatus $paymentStatus)
    {
        abort_if($paymentStatus->status !== "enabled", 404);
         return response()->json(["methods" => $paymentStatus, "message" => "Payment method fetched", ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentStatusRequest $request, PaymentStatus $paymentStatus)
    {
         $validated = $request->validated();
        $setting = $paymentStatus->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentStatus $paymentStatus)
    {
        //
    }
}
