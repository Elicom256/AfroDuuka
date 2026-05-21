<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportsSettingsRequest;
use App\Http\Requests\UpdateReportsSettingsRequest;
use App\Models\ReportsSettings;

class ReportsSettingsController extends Controller
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
    public function update(UpdateReportsSettingsRequest $request, ReportsSettings $reportsSettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReportsSettings $reportsSettings)
    {
        //
    }
}
