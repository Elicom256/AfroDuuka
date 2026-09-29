<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceSettingsRequest;
use App\Http\Requests\UpdateAttendanceSettingsRequest;
use App\Models\CoreSettings\AttendanceSettings;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


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
        $oldValues = $attendanceSetting->getAttributes();
        $attendanceSetting->update($validated);

        ActivityLog::create([
            'log_name' => 'settings',
            'description' => 'Attendance settings updated',
            'subject_type' => AttendanceSettings::class,
            'subject_id' => $attendanceSetting->id,
            'causer_type' => User::class,
            'causer_id' => Auth::id(),
            'properties' => [
                'old' => $oldValues,
                'attributes' => $validated,
            ],
        ]);

        return response()->json(["message" => "Setting updated", "setting" => $attendanceSetting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceSettings $attendanceSettings)
    {
        //
    }
}
