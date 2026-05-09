<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $business_id = Auth::user()->business_id;
        $categories = Category::where("business_id", $business_id)->orderBy("id", "asc")->get();
        return response()->json(["message"=>"Product categories fetched!", "categories" => $categories], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        //
        $validated = $request->validated();
        $category = Category::create($validated);
        return response()->json(["message" => "Category created successfully!", "category" => $category], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        $data = $category->update($validated);
        return response()->json(["message" => "Category updated successfully!", "category" => $data], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
        $category->delete();
        return response()->json(["message" => "Category with id $category->id deleted successfully!"], 201);
    }
}
