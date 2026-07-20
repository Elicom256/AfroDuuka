<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReorderRuleRequest;
use App\Http\Requests\UpdateReorderRuleRequest;
use App\Models\Product;
use App\Models\ReorderRule;
use Illuminate\Support\Facades\Auth;
/**
 * Manages automatic reorder rules for inventory products.
 */
class ReorderRuleController extends Controller
{
    public function index()
    {
        $rules = ReorderRule::where('business_id', Auth::user()->business_id)
            ->with(['product', 'preferredSupplier'])
            ->get();

        return response()->json(['message' => 'Fetched reorder rules', 'data' => $rules]);
    }

    public function store(StoreReorderRuleRequest $request)
    {
        $rule = ReorderRule::create($request->validated());
        //  update product re-order level 
        $rule->product->update(["reorder_level" => $rule->reorder_quantity]);
        return response()->json(['message' => 'Reorder rule created', 'data' => $rule], 201);
    }

    public function show(ReorderRule $reorderRule)
    {
        $reorderRule->load(['product', 'preferredSupplier']);
        return response()->json(['message' => 'Fetched reorder rule', 'data' => $reorderRule]);
    }

    public function update(UpdateReorderRuleRequest $request, ReorderRule $reorderRule)
    {
        $reorderRule->update($request->validated());
        $reorderRule->product->update(["reorder_level" => $reorderRule->reorder_quantity]);
        
        return response()->json(['message' => 'Reorder rule updated', 'data' => $reorderRule]);
    }

    public function destroy(ReorderRule $reorderRule)
    {
        $reorderRule->delete();
        return response()->json(['message' => 'Reorder rule deleted']);
    }
}
