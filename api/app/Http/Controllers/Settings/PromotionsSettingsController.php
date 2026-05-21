<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromotionsSettingsRequest;
use App\Http\Requests\UpdatePromotionsSettingsRequest;
use App\Models\PromotionsSettings;

class PromotionsSettingsController extends Controller
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
    public function store(StorePromotionsSettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PromotionsSettings $promotionsSettings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromotionsSettingsRequest $request, PromotionsSettings $promotionsSettings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromotionsSettings $promotionsSettings)
    {
        //
    }
}
