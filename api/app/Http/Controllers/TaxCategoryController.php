<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxCategoryRequest;
use App\Http\Requests\UpdateTaxCategoryRequest;
use App\Http\Resources\TaxCategoryResource;
use App\Models\Product;
use App\Models\TaxCategory;
use Illuminate\Http\JsonResponse;

class TaxCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TaxCategory::with('taxRates')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'message' => 'Tax categories fetched!',
            'categories' => TaxCategoryResource::collection($categories),
        ], 200);
    }

    public function store(StoreTaxCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $category = TaxCategory::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Tax category created successfully!',
            'category' => new TaxCategoryResource($category->load('taxRates')),
        ], 201);
    }

    public function show(TaxCategory $taxCategory): JsonResponse
    {
        return response()->json([
            'message' => 'Tax category fetched!',
            'category' => new TaxCategoryResource($taxCategory->load('taxRates')),
        ], 200);
    }

    public function update(UpdateTaxCategoryRequest $request, TaxCategory $taxCategory): JsonResponse
    {
        $taxCategory->update($request->validated());

        return response()->json([
            'message' => 'Tax category updated successfully!',
            'category' => new TaxCategoryResource($taxCategory->load('taxRates')),
        ], 200);
    }

    public function destroy(TaxCategory $taxCategory): JsonResponse
    {
        $inUse = Product::where('tax_category_id', $taxCategory->id)->exists()
            || $taxCategory->taxPayments()->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'Tax category cannot be deleted because it is referenced by products or tax payments. Deactivate it instead.',
            ], 422);
        }

        $taxCategory->delete();

        return response()->json([
            'message' => "Tax category with id {$taxCategory->id} deleted successfully!",
        ], 200);
    }
}