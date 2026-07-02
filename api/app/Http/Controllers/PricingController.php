<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePricingRequest;
use App\Http\Requests\UpdatePricingRequest;
use App\Models\Pricing;

class PricingController extends Controller
{
    public function index()
    {
        $pricings = Pricing::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return response()->json(["pricings" => $pricings, "message" => "Pricing plans retrieved"]);
    }

    public function store(StorePricingRequest $request)
    {
        $validated = $request->validated();
        $pricing = Pricing::create($validated);
        return response()->json(["pricing" => $pricing, "message" => "Pricing plan created"], 201);
    }

    public function show(Pricing $pricing)
    {
        return response()->json(["pricing" => $pricing, "message" => "Pricing plan retrieved"]);
    }

    public function update(UpdatePricingRequest $request, Pricing $pricing)
    {
        $validated = $request->validated();
        $pricing->update($validated);
        return response()->json(["pricing" => $pricing->fresh(), "message" => "Pricing plan updated"]);
    }

    public function destroy(Pricing $pricing)
    {
        $pricing->delete();
        return response()->json(["message" => "Pricing plan deleted"]);
    }
}
