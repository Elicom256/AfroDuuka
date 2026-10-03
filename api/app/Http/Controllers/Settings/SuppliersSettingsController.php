<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSuppliersSettingsRequest;
use App\Http\Requests\UpdateSuppliersSettingsRequest;
use App\Models\CoreSettings\SuppliersSettings;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
    public function show(SuppliersSettings $suppliersSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSuppliersSettingsRequest $request, SuppliersSettings $suppliersSetting)
    {
        $validated = $request->validated();
        $oldValues = $suppliersSetting->getAttributes();
        $suppliersSetting->update($validated);

        ActivityLog::create([
            'log_name' => 'settings',
            'description' => 'Supplier settings updated',
            'subject_type' => SuppliersSettings::class,
            'subject_id' => $suppliersSetting->id,
            'causer_type' => User::class,
            'causer_id' => Auth::id(),
            'properties' => [
                'old' => $oldValues,
                'attributes' => $validated,
            ],
        ]);

        return response()->json(["message" => "Setting updated", "setting" => $suppliersSetting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SuppliersSettings $suppliersSettings)
    {
        //
    }
}
