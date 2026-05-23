<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCreditSettingRequest;
use App\Http\Requests\UpdateCreditSettingRequest;
use App\Models\CoreSettings\CreditSetting;

class CreditSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = CreditSetting::first();
        return response()->json(["settings" =>$setting, "message" => "Customer settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCreditSettingRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CreditSetting $creditSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCreditSettingRequest $request, CreditSetting $creditSetting)
    {
         $validated = $request->validated();
        $setting = $creditSetting->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CreditSetting $creditSetting)
    {
        //
    }
}
