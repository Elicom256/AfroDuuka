<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceSettingsRequest;
use App\Http\Requests\UpdateAttendanceSettingsRequest;
use App\Models\CoreSettings\AttendanceSettings;


class AttendanceSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $setting = AttendanceSettings::first();
        return response()->json(["settings" =>$setting, "message" => "Attendance settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttendanceSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AttendanceSettings $attendanceSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceSettingsRequest $request, AttendanceSettings $attendanceSetting)
    {
         $validated = $request->validated();
        $setting = $attendanceSetting->update($validated);
        return response()->json(["message" => "Setting updated", "setting" => $setting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceSettings $attendanceSettings)
    {
        //
    }
}
