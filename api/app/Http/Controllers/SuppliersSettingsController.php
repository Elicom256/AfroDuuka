<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuppliersSettingsRequest;
use App\Http\Requests\UpdateSuppliersSettingsRequest;
use App\Models\CoreSettings\SuppliersSettings as CoreSettingsSuppliersSettings;

class SuppliersSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = CoreSettingsSuppliersSettings::first();
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
    public function show(SuppliersSettingsController $suppliersSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSuppliersSettingsRequest $request, SuppliersSettingsController $suppliersSettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SuppliersSettingsController $suppliersSettings)
    {
        //
    }
}
