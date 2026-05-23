<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomersSettingsRequest;
use App\Http\Requests\UpdateCustomersSettingsRequest;
use App\Models\CoreSettings\CustomersSettings;

class CustomersSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $setting = CustomersSettings::first();
        return response()->json(["settings" =>$setting, "message" => "Customer settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomersSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomersSettings $customersSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomersSettingsRequest $request, CustomersSettings $customersSetting)
    {
        $validated = $request->validated();
        $setting = $customersSetting->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomersSettings $customersSettings)
    {
        //
    }
}
