<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSuppliersSettingsRequest;
use App\Http\Requests\UpdateSuppliersSettingsRequest;
use App\Models\CoreSettings\SuppliersSettings;

class SuppliersSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = SuppliersSettings::first();
        return response()->json(["settings" =>$setting, "message" => "Supplier settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSuppliersSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SuppliersSettings $suppliersSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSuppliersSettingsRequest $request, SuppliersSettings $suppliersSetting)
    {
         $validated = $request->validated();
        $setting = $suppliersSetting->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SuppliersSettings $suppliersSettings)
    {
        //
    }
}
