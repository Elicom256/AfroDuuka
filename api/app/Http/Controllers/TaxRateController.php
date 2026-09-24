<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxRateRequest;
use App\Http\Requests\UpdateTaxRateRequest;
use App\Http\Resources\TaxRateResource;
use App\Models\TaxCategory;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;

class TaxRateController extends Controller
{
    public function index(): JsonResponse
    {
        $rates = TaxRate::with('taxCategory')
            ->whereHas('taxCategory')
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
        $this->assertOwnsCategory($taxRate);

        return response()->json([
            'message' => 'Tax rate fetched!',
            'rate' => new TaxRateResource($taxRate),
        ], 200);
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): JsonResponse
    {
        $this->assertOwnsCategory($taxRate);

        $taxRate->update($request->validated());

        return response()->json([
            'message' => 'Tax rate updated successfully!',
            'rate' => new TaxRateResource($taxRate),
        ], 200);
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $this->assertOwnsCategory($taxRate);

        $taxRate->delete();

        return response()->json([
            'message' => "Tax rate with id {$taxRate->id} deleted successfully!",
        ], 200);
    }

    private function assertOwnsCategory(TaxRate $taxRate): void
    {
        if (!TaxCategory::whereKey($taxRate->tax_category_id)->exists()) {
            abort(404, 'Tax rate not found.');
        }
    }
}