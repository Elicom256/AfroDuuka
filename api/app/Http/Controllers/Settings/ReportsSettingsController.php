<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportsSettingsRequest;
use App\Http\Requests\UpdateReportsSettingsRequest;
use App\Models\CoreSettings\ReportsSettings;


class ReportsSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = ReportsSettings::first();
        return response()->json(["settings" =>$setting, "message" => "Reports settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportsSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ReportsSettings $reportsSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReportsSettingsRequest $request, ReportsSettings $reportsSetting)
    {
         $validated = $request->validated();
        $setting = $reportsSetting->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReportsSettings $reportsSettings)
    {
        //
    }
}
