<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use App\Support\ListLimit;

class PlanController extends Controller
{
    public function index()
    {
        $plans = ListLimit::apply(
            Plan::orderBy('sort_order'),
            request(),
            ListLimit::REFERENCE
        );

        return response()->json([
            'plans' => $plans,
            'message' => 'Plans retrieved',
            ...ListLimit::truncationNotice(),
        ]);
    }

    public function store(StorePlanRequest $request)
    {
        $this->authorize('create', Plan::class);

        $validated = $request->validated();
        $plan = Plan::create($validated);

        return response()->json(['plan' => $plan, 'message' => 'Plan created'], 201);
    }

    public function show(Plan $plan)
    {
        $this->authorize('view', $plan);

        return response()->json(['plan' => $plan, 'message' => 'Plan retrieved']);
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();
        $plan->update($validated);

        return response()->json(['plan' => $plan->fresh(), 'message' => 'Plan updated']);
    }

    public function destroy(Plan $plan)
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return response()->json(['message' => 'Plan deleted']);
    }
}
