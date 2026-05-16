<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessBranchProductRequest;
use App\Http\Requests\UpdateBusinessBranchProductRequest;
use App\Models\BusinessBranchProduct;
use Illuminate\Support\Facades\Auth;

class BusinessBranchProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = Auth::user()->business_branch_id;
        $products = BusinessBranchProduct::where("business_branch_id", $branchId)->get();
        return response()->json(["message" => "Products fetched", "products" => $products], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessBranchProductRequest $request)
    {
        $validated = $request->validated();
        dd($validated);
        $product = BusinessBranchProduct::create($validated);
        return response()->json(["message" => "Product Created Successfully!", "product" => $product], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessBranchProduct $businessBranchProduct)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BusinessBranchProduct $businessBranchProduct)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessBranchProductRequest $request, BusinessBranchProduct $businessBranchProduct)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessBranchProduct $businessBranchProduct)
    {
        //
    }
}
