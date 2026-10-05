<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Models\StockMovement;

class StockMovementController extends Controller
{
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
    public function store(StoreStockMovementRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStockMovementRequest $request, StockMovement $stockMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Remove the specified resource from storage.
     *
     * Refuses rather than silently succeeding (checked.md P1-24). A stock movement is
     * the audit trail of a quantity change: deleting one would leave the ledger
     * disagreeing with the stock level it is supposed to explain, and
     * stock_movements.reference_type/reference_id carry no foreign key to check against.
     * Corrections are made by recording a reversing movement, not by erasing the
     * original.
     */
    public function destroy(StockMovement $stockMovement)
    {
        abort(405, 'Stock movements are an audit trail and cannot be deleted. Record a reversing movement instead.');
    }
}
