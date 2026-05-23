<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromotionsSettingsRequest;
use App\Http\Requests\UpdatePromotionsSettingsRequest;
use App\Models\CoreSettings\PromotionsSettings;

class PromotionsSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = PromotionsSettings::first();
        return response()->json(["settings" =>$setting, "message" => "Promotions settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromotionsSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PromotionsSettings $promotionsSettings)
    {
        return response()->json(["message" => "Setting updated", "setting" => $promotionsSettings]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromotionsSettingsRequest $request, PromotionsSettings $promotionsSetting)
    {
         $validated = $request->validated();
        $setting = $promotionsSetting->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromotionsSettings $promotionsSettings)
    {
        //
    }
}
