<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $business_id = Auth::user()->business_id;
        $categories = ProductCategory::where("business_id", $business_id)->orderBy("id", "asc")->get();
        return response()->json(["message"=>"Product categories fetched!", "categories" => $categories], 200);
    }

    public function store(StoreProductCategoryRequest $request)
    {
        $validated = $request->validated();
        $category = ProductCategory::create($validated);
        return response()->json(["message" => "Category created successfully!", "category" => $category], 201);
    }

    public function show(ProductCategory $productCategory)
    {
        return response()->json(["message" => "Category fetched!", "category" => $productCategory], 200);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory)
    {
        $validated = $request->validated();
        $productCategory->update($validated);
        return response()->json(["message" => "Category updated successfully!", "category" => $productCategory], 201);
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();
        return response()->json(["message" => "Category with id $productCategory->id deleted successfully!"], 201);
    }
}
