<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessBranchProductRequest;
use App\Http\Requests\UpdateBusinessBranchProductRequest;
use App\Models\BusinessBranchProduct;
use App\Services\BusinessBranchProductService;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\TryCatch;

class BusinessBranchProductController extends Controller
{
    protected BusinessBranchProductService $businessBranchProductService;
    public function __construct(BusinessBranchProductService $businessBranchProductService)
    {
        $this->businessBranchProductService = $businessBranchProductService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = Auth::user()->business_branch_id;
        $products = BusinessBranchProduct::where("business_branch_id", $branchId)->with("product")->get();
        return response()->json(["message" => "Products fetched", "products" => $products], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessBranchProductRequest $request)
    {
        $validated = $request->validated();
        $product = BusinessBranchProduct::create($validated);
        return response()->json(["message" => "Product Created Successfully!", "product" => $product], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $businessBranchProduct)
    {
        $branchId = Auth::user()->business_branch_id;
        $product = BusinessBranchProduct::where("id", $businessBranchProduct)
                   ->where("business_branch_id", $branchId)
                   ->with("product")->first();
        // dd($businessBranchProduct);

        return response()->json(["message" => "Product Fetched Successfully!", "product" => $product], 200);
    }

    public function inventoryAnalytics(){
        
        try {
            $business_branch_id = Auth::user()->business_branch_id;
            $inventory = $this->businessBranchProductService->analytics($business_branch_id);
            return response()->json([
            "message" => "Failed to fetch inventory analytics!",
            "data" => $inventory
           ]);
        } catch (\Exception $e) {
           return response()->json([
            "message" => "Failed to fetch inventory analytics!",
            "error" => $e->getMessage()
           ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessBranchProductRequest $request, BusinessBranchProduct $businessBranchProduct)
    {
        $validated = $request->validated();
        $businessBranchProduct->update($validated);
        return response()->json(["message" => "Product Updated Successfully!", "product" => $businessBranchProduct], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessBranchProduct $businessBranchProduct)
    {
        $businessBranchProduct->delete();
        return response()->json(["message" => "Product Deleted Successfully!", "product" => $businessBranchProduct], 201);

    }
}
