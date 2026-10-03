<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomersSettingsRequest;
use App\Http\Requests\UpdateCustomersSettingsRequest;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CustomersSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $setting = CustomersSettings::first();
        return response()->json(["settings" =>$setting, "message" => "Customer settings"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomersSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomersSettings $customersSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomersSettingsRequest $request, CustomersSettings $customersSetting)
    {
        $validated = $request->validated();
        $oldValues = $customersSetting->getAttributes();
        $customersSetting->update($validated);

        ActivityLog::create([
            'log_name' => 'settings',
            'description' => 'Customer settings updated',
            'subject_type' => CustomersSettings::class,
            'subject_id' => $customersSetting->id,
            'causer_type' => User::class,
            'causer_id' => Auth::id(),
            'properties' => [
                'old' => $oldValues,
                'attributes' => $validated,
            ],
        ]);

        return response()->json(["message" => "Setting updated", "setting" => $customersSetting]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomersSettings $customersSettings)
    {
        //
    }
}
