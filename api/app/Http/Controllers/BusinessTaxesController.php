<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessTaxesRequest;
use App\Http\Requests\UpdateBusinessTaxesRequest;
use App\Models\ActivityLog;
use App\Models\BusinessTaxes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BusinessTaxesController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $query = BusinessTaxes::with('businessBranch')
            ->where('business_id', $user->business_id);

        if ($user->business_branch_id) {
            $query->where('business_branch_id', $user->business_branch_id);
        }

        $taxes = $query->orderBy('id', 'desc')->paginate(15);

        return response()->json([
            'message' => 'Fetched business taxes',
            'business_taxes' => $taxes,
        ]);
    }

    public function store(StoreBusinessTaxesRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['business_id'] = $user->business_id;
        $validated['business_branch_id'] = $validated['business_branch_id'] ?? $user->business_branch_id;

        $tax = BusinessTaxes::create($validated);

        ActivityLog::log(
            $user,
            'created_business_tax',
            $tax,
            sprintf('Created business tax "%s" for branch id %s.', $tax->name, $tax->business_branch_id),
            ['rate' => $tax->rate, 'type' => $tax->type]
        );

        return response()->json([
            'message' => 'Business tax created successfully',
            'business_tax' => $tax,
        ], 201);
    }

    public function show(BusinessTaxes $businessTaxes): JsonResponse
    {
        $businessTaxes->load('businessBranch');

        return response()->json([
            'message' => 'Fetched business tax',
            'business_tax' => $businessTaxes,
        ]);
    }

    public function update(UpdateBusinessTaxesRequest $request, BusinessTaxes $businessTaxes): JsonResponse
    {
        $validated = $request->validated();
        $businessTaxes->update($validated);

        ActivityLog::log(
            Auth::user(),
            'updated_business_tax',
            $businessTaxes,
            sprintf('Updated business tax "%s".', $businessTaxes->name),
            ['changes' => $validated]
        );

        return response()->json([
            'message' => 'Business tax updated successfully',
            'business_tax' => $businessTaxes,
        ]);
    }

    public function destroy(BusinessTaxes $businessTaxes): JsonResponse
    {
        $label = $businessTaxes->name;
        $businessTaxes->delete();

        ActivityLog::log(
            Auth::user(),
            'deleted_business_tax',
            $businessTaxes,
            sprintf('Deleted business tax "%s".', $label)
        );

        return response()->json([
            'message' => sprintf('Deleted business tax "%s".', $label),
        ]);
    }
}
