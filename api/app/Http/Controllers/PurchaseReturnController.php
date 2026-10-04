<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use App\Support\Auth\RolePermissions;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    protected PurchaseReturnService $purchaseReturnService;

    public function __construct(PurchaseReturnService $purchaseReturnService)
    {
        $this->purchaseReturnService = $purchaseReturnService;
    }

    public function index()
    {
        $purchaseReturns = PurchaseReturn::with('purchaseReturnItems.purchaseItem.product', 'supplier', 'processedByUser')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['message' => 'All purchase returns fetched', 'purchase_returns' => $purchaseReturns]);
    }

    public function store(StorePurchaseReturnRequest $request)
    {
        // Stock back out to a supplier. Held to canModifyStock() for the same reason
        // as the sale return opposite: this moves real quantity, and nothing beneath
        // this line asked which role was asking.
        abort_unless(RolePermissions::canModifyStock($request->user()), 403, 'You cannot process purchase returns.');

        $validated = $request->validated();
        $purchaseReturn = $this->purchaseReturnService->handleCreatePurchaseReturn($validated);
        return response()->json(['message' => 'Purchase return processed successfully!', 'purchase_return' => $purchaseReturn], 200);
    }

    public function show(string $purchaseReturn)
    {
        $purchaseReturn = PurchaseReturn::with('purchaseReturnItems.purchaseItem.product', 'supplier', 'processedByUser')
            ->findOrFail($purchaseReturn);
        return response()->json(['message' => 'Purchase return fetched!', 'purchase_return' => $purchaseReturn]);
    }
}
