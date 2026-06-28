<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessBranchRequest;
use App\Http\Requests\UpdateBusinessBranchRequest;
use App\Models\BusinessBranch;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class BusinessBranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $businessId = Auth::user()->business_id;
        $branches = BusinessBranch::with("business.country")->where("business_id", $businessId)->orderBy("id")->get();
        return response()->json(["message" => "Fetched all business branches", "branches" => $branches]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function salesAndPurchases()
    {
        $business_branch_id = Auth::user()->business_branch_id;
        $totalSales = Sale::where("business_branch_id", $business_branch_id)->sum("total_amount");
        $totalPurchases = Purchase::where("business_branch_id", $business_branch_id)->sum("total_amount");
        return response()->json([
            "message" => "Fetched new changes",
            "totalSales" => $totalSales,
            "totalPurchases" => $totalPurchases
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessBranchRequest $request)
    {
        $validated = $request->validated();
        $branch = BusinessBranch::create($validated);
        return response()->json(["message" => "Added a new branch!", "branch" => $branch], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessBranch $branch)
    {
       
        // $test = BusinessBranch::find($branch);
        //  dd($test);
        return response()->json(["message" => "Fetched branch!", "branch" => $branch], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessBranchRequest $request, BusinessBranch $branch)
    {
        $validated = $request->validated();
        $branch->update($validated);
        
        return response()->json([
            "message" => "updated $branch->name branch!",
             "branch" => $branch
             ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessBranch $branch)
    {
        //
    }
}
