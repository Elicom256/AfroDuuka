<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Sale;
use App\Services\SaleItemService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class SaleController extends Controller
{
    protected $saleItemService;
    public function __construct(SaleItemService $saleItemService)
    {
        $this->saleItemService = $saleItemService;
    }
    
    public function index()
    {
        $user = Auth::user();
        if($user->role !== "admin"){
            $sales = Sale::with("saleItems", "businessBranch")
                     ->where("business_branch_id", $user->business_branch_id)
                     ->orderByDesc("created_at")->get();
        }else{
            $sales = Sale::with("saleItems", "businessBranch")
                     ->where("businessBranch", function($q) use($user){
                             $q->where("business_id", $user->business_id);
                         })
                     ->orderByDesc("created_at")
                     ->get();
        }

        return response()->json(["message" => "All sales fetched", "sales" => $sales]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        // dd($request);
        $business_branch_id = Auth::user()->business_branch_id;
        $validated = $request->validated();
        // dd($validated);
        $sale = $this->saleItemService->handleSaveSaleItem($validated, $business_branch_id);
        return response()->json([ 'message' => 'Sale completed successfully!', 'sale' => $sale ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $sale)
    {
        $sale = Sale::find($sale)->load("saleItems");
        return response()->json(["message" => "Sale Fetched!", "sale" => $sale]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        $validated = $request->validated();
        $sale->update($validated);
        return response()->json(["message" => "Sale Updated", "sale" => $sale]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        //
    }
}
