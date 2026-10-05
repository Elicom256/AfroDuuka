<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleReturnRequest;
use App\Models\SaleReturn;
use App\Services\SaleReturnService;
use App\Support\Auth\RolePermissions;
use Illuminate\Support\Facades\Auth;

class SaleReturnController extends Controller
{
    protected SaleReturnService $saleReturnService;

    public function __construct(SaleReturnService $saleReturnService)
    {
        $this->saleReturnService = $saleReturnService;
    }

    public function index()
    {
        $saleReturns = SaleReturn::with('saleReturnItems.saleItem.product', 'processedByUser')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['message' => 'All sale returns fetched', 'sale_returns' => $saleReturns]);
    }

    public function store(StoreSaleReturnRequest $request)
    {
        // Stock back in and a refund out, in one write. canModifyStock() is the
        // existing expression of that capability.
        abort_unless(RolePermissions::canModifyStock($request->user()), 403, 'You cannot process sale returns.');

        $business_branch_id = Auth::user()->business_branch_id;
        $validated = $request->validated();
        $saleReturn = $this->saleReturnService->handleCreateSaleReturn($validated, $business_branch_id);

        return response()->json(['message' => 'Sale return processed successfully!', 'sale_return' => $saleReturn], 200);
    }

    public function show(string $saleReturn)
    {
        $saleReturn = SaleReturn::with('saleReturnItems.saleItem.product', 'processedByUser')
            ->findOrFail($saleReturn);

        return response()->json(['message' => 'Sale return fetched!', 'sale_return' => $saleReturn]);
    }

    public function update(StoreSaleReturnRequest $request, string $saleReturn)
    {
        abort_unless(RolePermissions::canModifyStock($request->user()), 403, 'You cannot process sale returns.');

        $saleReturn = SaleReturn::findOrFail($saleReturn);
        $validated = $request->validated();
        $updated = $this->saleReturnService->handleUpdateSaleReturn($saleReturn, $validated);

        return response()->json(['message' => 'Sale return updated successfully!', 'sale_return' => $updated]);
    }

    public function destroy(string $saleReturn)
    {
        abort_unless(RolePermissions::canModifyStock(request()->user()), 403, 'You cannot process sale returns.');

        $saleReturn = SaleReturn::findOrFail($saleReturn);
        $this->saleReturnService->handleDeleteSaleReturn($saleReturn);

        return response()->json(['message' => 'Sale return deleted successfully!']);
    }
}
