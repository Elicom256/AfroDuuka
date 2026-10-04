<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromotionsSettingsRequest;
use App\Http\Requests\UpdatePromotionsSettingsRequest;
use App\Models\ActivityLog;
use App\Models\CoreSettings\PromotionsSettings;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PromotionsSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = PromotionsSettings::first();

        return response()->json(['settings' => $setting, 'message' => 'Promotions settings']);
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
    public function show(PromotionsSettings $promotionsSetting)
    {
        return response()->json(['message' => 'Setting updated', 'setting' => $promotionsSetting]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromotionsSettingsRequest $request, PromotionsSettings $promotionsSetting)
    {
        $validated = $request->validated();
        $oldValues = $promotionsSetting->getAttributes();
        $promotionsSetting->update($validated);

        ActivityLog::create([
            'log_name' => 'settings',
            'description' => 'Promotions settings updated',
            'subject_type' => PromotionsSettings::class,
            'subject_id' => $promotionsSetting->id,
            'causer_type' => User::class,
            'causer_id' => Auth::id(),
            'properties' => [
                'old' => $oldValues,
                'attributes' => $validated,
            ],
        ]);

        return response()->json(['message' => 'Setting updated', 'setting' => $promotionsSetting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromotionsSettings $promotionsSettings)
    {
        //
    }
}
