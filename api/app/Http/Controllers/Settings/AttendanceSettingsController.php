<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceSettingsRequest;
use App\Http\Requests\UpdateAttendanceSettingsRequest;
use App\Models\AttendanceSettings;

class AttendanceSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function update(UpdateAttendanceSettingsRequest $request, AttendanceSettings $attendanceSettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceSettings $attendanceSettings)
    {
        //
    }
}
