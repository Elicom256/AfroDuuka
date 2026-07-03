<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::with(["pricing", "business"])->get();
        return response()->json(["plans" => $plans, "message" => "Plans retrieved"]);
    }

    public function store(StorePlanRequest $request)
    {
        $validated = $request->validated();
        $plan = Plan::create($validated);
        return response()->json(["plan" => $plan->load(["pricing", "business"]), "message" => "Plan created"], 201);
    }

    public function show(Plan $plan)
    {
        return response()->json(["plan" => $plan->load(["pricing", "business"]), "message" => "Plan retrieved"]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $validated = $request->validated();
        $plan->update($validated);
        return response()->json(["plan" => $plan->fresh()->load(["pricing", "business"]), "message" => "Plan updated"]);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json(["message" => "Plan deleted"]);
    }
}
