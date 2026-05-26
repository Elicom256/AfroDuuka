<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    protected $supplierService;
    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $business_id = Auth::user()->business_id;
        $suppliers = Supplier::with("user")->get();
        return response()->json(["message" => "Fetched suppliers", "suppliers" => $suppliers]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        $supplier = $this->supplierService->createSupplier($validated);
        return response()->json(["message" => "Added supplier", "supplier" => $supplier]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        //
    }
}
