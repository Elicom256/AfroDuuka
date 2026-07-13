<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleReturnRequest;
use App\Models\SaleReturn;
use App\Services\SaleReturnService;
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
        $user = Auth::user();
        if ($user->role !== 'admin') {
            $saleReturns = SaleReturn::with('saleReturnItems.saleItem.product', 'processedByUser')
                ->where('business_branch_id', $user->business_branch_id)
                ->orderByDesc('created_at')
                ->get();
        } else {
            $saleReturns = SaleReturn::with('saleReturnItems.saleItem.product', 'processedByUser')
                ->whereHas('businessBranch', function ($q) use ($user) {
                    $q->where('business_id', $user->business_id);
                })
                ->orderByDesc('created_at')
                ->get();
        }

        return response()->json(['message' => 'All sale returns fetched', 'sale_returns' => $saleReturns]);
    }

    public function store(StoreSaleReturnRequest $request)
    {
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
}
