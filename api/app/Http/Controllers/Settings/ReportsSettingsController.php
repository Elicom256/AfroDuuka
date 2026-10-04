<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportsSettingsRequest;
use App\Http\Requests\UpdateReportsSettingsRequest;
use App\Models\ActivityLog;
use App\Models\CoreSettings\ReportsSettings;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ReportsSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = ReportsSettings::first();

        return response()->json(['settings' => $setting, 'message' => 'Reports settings']);
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
    public function show(ReportsSettings $reportsSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReportsSettingsRequest $request, ReportsSettings $reportsSetting)
    {
        $validated = $request->validated();
        $oldValues = $reportsSetting->getAttributes();
        $reportsSetting->update($validated);

        ActivityLog::create([
            'log_name' => 'settings',
            'description' => 'Reports settings updated',
            'subject_type' => ReportsSettings::class,
            'subject_id' => $reportsSetting->id,
            'causer_type' => User::class,
            'causer_id' => Auth::id(),
            'properties' => [
                'old' => $oldValues,
                'attributes' => $validated,
            ],
        ]);

        return response()->json(['message' => 'Setting updated', 'setting' => $reportsSetting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReportsSettings $reportsSettings)
    {
        //
    }
}
