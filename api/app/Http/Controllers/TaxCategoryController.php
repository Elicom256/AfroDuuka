<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxCategoryRequest;
use App\Http\Requests\UpdateTaxCategoryRequest;
use App\Http\Resources\TaxCategoryResource;
use App\Models\Product;
use App\Models\TaxCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaxCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $branchId = Auth::user()->business_branch_id;

        $categories = TaxCategory::with('taxRates')
            ->where('business_branch_id', $branchId)
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
            'business_branch_id' => Auth::user()->business_branch_id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Tax category created successfully!',
            'category' => new TaxCategoryResource($category->load('taxRates')),
        ], 201);
    }

    public function show(TaxCategory $taxCategory): JsonResponse
    {
        $this->assertOwnBranch($taxCategory);

        return response()->json([
            'message' => 'Tax category fetched!',
            'category' => new TaxCategoryResource($taxCategory->load('taxRates')),
        ], 200);
    }

    public function update(UpdateTaxCategoryRequest $request, TaxCategory $taxCategory): JsonResponse
    {
        $this->assertOwnBranch($taxCategory);

        $taxCategory->update($request->validated());

        return response()->json([
            'message' => 'Tax category updated successfully!',
            'category' => new TaxCategoryResource($taxCategory->load('taxRates')),
        ], 200);
    }

    public function destroy(TaxCategory $taxCategory): JsonResponse
    {
        $this->assertOwnBranch($taxCategory);

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

    private function assertOwnBranch(TaxCategory $taxCategory): void
    {
        if ($taxCategory->business_branch_id !== Auth::user()->business_branch_id) {
            abort(404, 'Tax category not found.');
        }
    }
}