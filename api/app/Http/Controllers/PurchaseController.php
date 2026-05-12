<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;

class PurchaseController extends Controller
{
    protected $purchaseService;
    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchases = Purchase::with("supplier", "purchaseItems")->orderByDesc("created_at")->get();
        return response()->json(["message" => "Purchases fetched", "purchases" => $purchases]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();
        $purchase = $this->purchaseService->savePurchase($validated);
        return response()->json(["message" => "Added purchase", "supplier" => $purchase]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $purchase)
    {
        $product = Purchase::with("supplier", "purchaseItems")->where("id", $purchase) ->orderByDesc("created_at")->first();
        return response()->json(["message" => "Purchase fetched", "purchase" => $product]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        //
    }
}
