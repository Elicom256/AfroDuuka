<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleItemRequest;
use App\Http\Requests\UpdateSaleItemRequest;
use App\Models\SaleItem;
use App\Services\SaleItemService;
use Illuminate\Support\Facades\Auth;

class SaleItemController extends Controller
{
    protected $saleItemService;
    public function __construct(SaleItemService $saleItemService)
    {
        $this->saleItemService = $saleItemService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleItemRequest $request)
    {
        dd($request);
        $businessId = Auth::user()->business_id;
        $validated = $request->validated();
        
        $values = $this->saleItemService->handleSaveSaleItem($validated, $businessId);
        return response()->json([ 'message' => 'Sale successfully', 'sale' => $values ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(SaleItem $saleItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSaleItemRequest $request, SaleItem $saleItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SaleItem $saleItem)
    {
        //
    }
}
