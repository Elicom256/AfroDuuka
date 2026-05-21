<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomersSettingsRequest;
use App\Http\Requests\UpdateCustomersSettingsRequest;
use App\Models\CustomersSettings;

class CustomersSettingsController extends Controller
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
    public function store(StoreCustomersSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomersSettings $customersSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomersSettingsRequest $request, CustomersSettings $customersSettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomersSettings $customersSettings)
    {
        //
    }
}
