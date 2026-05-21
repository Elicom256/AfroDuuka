<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentStatusRequest;
use App\Http\Requests\UpdatePaymentStatusRequest;
use App\Models\PaymentStatus;

class PaymentStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentStatusRequest $request, PaymentStatus $paymentStatus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentStatus $paymentStatus)
    {
        //
    }
}
