<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessBranchRequest;
use App\Http\Requests\UpdateBusinessBranchRequest;
use App\Models\BusinessBranch;
use Illuminate\Support\Facades\Auth;

class BusinessBranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $businessId = Auth::user()->business_id;
        $branches = BusinessBranch::where("business_id", $businessId)->orderBy("id")->get();
        return response()->json(["message" => "Fetched all business branches", "branches" => $branches]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function show(BusinessBranch $businessBranch)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BusinessBranch $businessBranch)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessBranchRequest $request, BusinessBranch $businessBranch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessBranch $businessBranch)
    {
        //
    }
}
