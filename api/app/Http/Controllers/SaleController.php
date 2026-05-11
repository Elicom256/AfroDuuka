<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $businessId = Auth::user()->business_id;
        $sales = Sale::with("saleItems")->where("business_id", $businessId)->orderByDesc("created_at")->get();
        return response()->json(["message" => "All sales fetched", "sales" => $sales]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        //
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        //
    }
}
