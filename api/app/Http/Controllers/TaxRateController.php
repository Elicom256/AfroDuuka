<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxRateRequest;
use App\Http\Requests\UpdateTaxRateRequest;
use App\Http\Resources\TaxRateResource;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaxRateController extends Controller
{
    public function index(): JsonResponse
    {
        $branchId = Auth::user()->business_branch_id;

        $rates = TaxRate::with('taxCategory')
            ->whereHas('taxCategory', fn ($q) => $q->where('business_branch_id', $branchId))
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'message' => 'Tax rates fetched!',
            'rates' => TaxRateResource::collection($rates),
        ], 200);
    }

    public function store(StoreTaxRateRequest $request): JsonResponse
    {
        $rate = TaxRate::create($request->validated());

        return response()->json([
            'message' => 'Tax rate created successfully!',
            'rate' => new TaxRateResource($rate),
        ], 201);
    }

    public function show(TaxRate $taxRate): JsonResponse
    {
        $this->assertOwnBranch($taxRate);

        return response()->json([
            'message' => 'Tax rate fetched!',
            'rate' => new TaxRateResource($taxRate),
        ], 200);
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        $this->assertOwnBranch($taxRate);

        $taxRate->update($request->validated());

        return response()->json([
            'message' => 'Tax rate updated successfully!',
            'rate' => new TaxRateResource($taxRate),
        ], 200);
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $this->assertOwnBranch($taxRate);

        $taxRate->delete();

        return response()->json([
            'message' => "Tax rate with id {$taxRate->id} deleted successfully!",
        ], 200);
    }

    private function assertOwnBranch(TaxRate $taxRate): void
    {
        $taxRate->loadMissing('taxCategory');

        if (
            !$taxRate->taxCategory
            || $taxRate->taxCategory->business_branch_id !== Auth::user()->business_branch_id
        ) {
            abort(404, 'Tax rate not found.');
        }
    }
}