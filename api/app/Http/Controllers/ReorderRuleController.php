<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReorderRuleRequest;
use App\Http\Requests\UpdateReorderRuleRequest;
use App\Models\ReorderRule;

/**
 * Manages automatic reorder rules for inventory products.
 */
class ReorderRuleController extends Controller
{
    public function index()
    {
        $rules = ReorderRule::where('business_id', auth()->user()->business_id)
            ->with(['businessBranchProduct', 'preferredSupplier'])
            ->get();

        return response()->json(['message' => 'Fetched reorder rules', 'data' => $rules]);
    }

    public function store(StoreReorderRuleRequest $request)
    {
        $rule = ReorderRule::create($request->validated());
        return response()->json(['message' => 'Reorder rule created', 'data' => $rule], 201);
    }

    public function show(ReorderRule $reorderRule)
    {
        $reorderRule->load(['businessBranchProduct', 'preferredSupplier']);
        return response()->json(['message' => 'Fetched reorder rule', 'data' => $reorderRule]);
    }

    public function update(UpdateReorderRuleRequest $request, ReorderRule $reorderRule)
    {
        $reorderRule->update($request->validated());
        return response()->json(['message' => 'Reorder rule updated', 'data' => $reorderRule]);
    }

    public function destroy(ReorderRule $reorderRule)
    {
        $reorderRule->delete();
        return response()->json(['message' => 'Reorder rule deleted']);
    }
}
