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
        $branches = BusinessBranch::with('business.country')->where('business_id', $businessId)->orderBy('id')->get();

        return response()->json(['message' => 'Fetched all business branches', 'branches' => $branches]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function salesAndPurchases()
    {
        $totalSales = Sale::sum('total_amount');
        $totalPurchases = Purchase::sum('total_amount');

        return response()->json([
            'message' => 'Fetched new changes',
            'totalSales' => $totalSales,
            'totalPurchases' => $totalPurchases,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessBranchRequest $request)
    {
        $validated = $request->validated();
        $branch = BusinessBranch::create($validated);

        return response()->json(['message' => 'Added a new branch!', 'branch' => $branch], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessBranch $branch)
    {
        return response()->json(['message' => 'Fetched branch!', 'branch' => $branch], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessBranchRequest $request, BusinessBranch $branch)
    {
        $validated = $request->validated();
        $branch->update($validated);

        return response()->json([
            'message' => "updated $branch->name branch!",
            'branch' => $branch,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * Refuses rather than deleting. This was an empty method that still answered 200,
     * so a caller was told a branch was gone while it was still there — and the moment
     * it stopped being empty the database would have taken the branch away for real,
     * because sales.business_branch_id, purchases and 25 other tables cascade on delete.
     * That is the reviewed defect in checked.md P1-24 (a no-op delete that reports
     * success) and the data-loss hazard underneath it.
     *
     * Closing a branch is a business decision that needs stock moved, open orders settled
     * and reports re-pointed, so it is not something an API call should do by surprise.
     */
    public function destroy(BusinessBranch $branch)
    {
        abort(405, 'Branches cannot be deleted once trading has started. Close the branch instead.');
    }
}
