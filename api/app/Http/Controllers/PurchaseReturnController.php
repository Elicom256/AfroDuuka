<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
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
