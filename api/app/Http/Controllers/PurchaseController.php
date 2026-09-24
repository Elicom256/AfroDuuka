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
        $purchases = Purchase::with("supplier", "purchaseItems", "businessBranch")
                    ->orderByDesc("created_at")
                    ->get();

        return response()->json(["message" => "Purchases fetched", "purchases" => $purchases]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();
        $purchase = $this->purchaseService->savePurchase($validated);
        return response()->json(["message" => "Purchase Completed Successfully!", "purchase" => $purchase]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $purchase)
    {
        $product = Purchase::with("supplier", "purchaseItems.product")
                  ->findOrFail($purchase);
        return response()->json(["message" => "Purchase fetched", "purchase" => $product]);
    }

    public function salesAnalytics()
{
    try {
        $period = request()->query('period', 'last_7_days');
        $allowedPeriods = ['last_7_days', 'last_30_days', 'this_month', 'last_month'];
        if (!in_array($period, $allowedPeriods)) {
            $period = 'last_7_days'; // fallback
        }
        $analytics = $this->purchaseService->analytics($period);

        return response()->json([
            "message" => "Sales analytics fetched successfully",
            "data" => $analytics
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            "message" => "Failed to fetch sales analytics",
            "error" => $e->getMessage()
        ], 500);
    }
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
