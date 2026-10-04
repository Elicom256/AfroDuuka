<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        // PurchaseOrderController has always gated its store() inline; this twin of it
        // did not, so a purchase commitment to a supplier could be written by any
        // signed-in account. canCreatePurchaseOrder() is the same capability, and it
        // is the one that admits Procurement — recording what was bought is its job.
        abort_unless(RolePermissions::canCreatePurchaseOrder($request->user()), 403, 'You cannot record purchases.');

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

    /**
     * Receiving is the write that moves real stock.
     *
     * receivePurchase() increments product quantity and rewrites cost_price for every
     * line, so this is the highest-value write in the module: an unrestricted caller
     * could inflate stock and overwrite the cost basis the business prices from. The
     * role middleware on this route group only checks that the caller holds *some*
     * role, so the capability has to be named here. Procurement may receive because
     * goods arriving is its job; Operations may not, because it would be checking in
     * stock it never ordered.
     */
    public function receive(Purchase $purchase, Request $request): JsonResponse
    {
        abort_unless(RolePermissions::canReceivePurchaseOrder(Auth::user()), 403, 'You cannot receive purchase orders.');

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.quantity' => 'required|integer|min:0',
        ]);

        $receivedItems = [];
        foreach ($request->input('items', []) as $item) {
            $receivedItems[(int) $item['purchase_item_id']] = (int) $item['quantity'];
        }

        $updatedPurchase = $this->purchaseService->receivePurchase($purchase, $receivedItems, auth()->id());

        return response()->json([
            'message' => 'Purchase received and stock updated successfully.',
            'purchase' => $updatedPurchase,
        ]);
    }

    public function salesAnalytics()
{
    try {
        $period = request()->query('period', 'last_7_days');
        $allowedPeriods = ['today', 'last_7_days', 'last_30_days', 'this_month', 'last_month'];
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
        // The role check runs first so a refused caller gets an honest 403 rather than
        // the 422 below, which reads as "this endpoint does not accept edits" and so
        // tells an Operations account nothing about whether the route is theirs.
        abort_unless(RolePermissions::canCreatePurchaseOrder($request->user()), 403, 'You cannot edit purchases.');

        // The route has always been published by Route::resource, but this body was a
        // bare `//` comment, so it returned null and Laravel failed the response type.
        //
        // It stays refused rather than implemented on purpose. A purchase is not a
        // free-standing draft: receiving one has already incremented product quantity
        // and rewritten cost_price for every line, so editing the header afterwards
        // would leave stock and cost basis describing a purchase that no longer reads
        // the same. Correcting a received purchase is a stock adjustment, which is
        // what the stock-movement endpoints are for. Use cancel/receive state changes
        // rather than rewriting history.
        abort(422, 'Purchases cannot be edited after creation. Adjust stock through the stock movement endpoints instead.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        // Refused for the same reason as update(): a received purchase is referenced by
        // stock_movements and by the cost basis of every product it touched. Deleting
        // the header would cascade those away and leave inventory that cannot be
        // reconciled. The role gate is the central DELETE middleware (canDelete); this
        // is the business rule on top of it.
        abort(422, 'Purchases cannot be deleted. Cancel or adjust them instead.');
    }
}
