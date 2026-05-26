<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $products = Product::all();
        return response()->json(["message"=>"Products fetched!", "products" => $products], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $product = Product::create($validated);
        return response()->json(["message" => "Added new product", "product" => $product], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $business_product)
    {
        // $product = Product::find($pdt);
        return response()->json(["message"=>"Product fetched!", "product" => $business_product], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $business_product)
    {
         $validated = $request->validated();
        $business_product->update($validated);
        return response()->json(["message" => "Product Updated", "product" => $business_product], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $product)
    {
        Product::find($product)->delete();
        return response()->json(["message"=>"Product with id $product deleted!"], 201);
    }
}
