<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDebitSettingRequest;
use App\Http\Requests\UpdateDebitSettingRequest;
use App\Models\CoreSettings\DebitSetting;

class DebitSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = DebitSetting::first();
        return response()->json(["settings" =>$setting, "message" => "Customer settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDebitSettingRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DebitSetting $debitSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDebitSettingRequest $request, DebitSetting $debitSetting)
    {
         $validated = $request->validated();
        $setting = $debitSetting->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DebitSetting $debitSetting)
    {
        //
    }
}
