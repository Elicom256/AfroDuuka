<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\CoreSettings\SuppliersSettings;
use App\Models\Supplier;
use App\Services\SupplierService;
use GuzzleHttp\Psr7\Response;
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
        $allowed = SuppliersSettings::value("status");
        abort_if($allowed !== "enabled", 'Supplier creation is disabled.', 403);
        $validated = $request->validated();
        $supplier = $this->supplierService->createSupplier($validated);
        return response()->json(["message" => "Added supplier", "supplier" => $supplier]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load("user");
        return response()->json(["message" => "Supplier Fetched Successfully!", "supplier" => $supplier]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        //
        $validated = $request->validated();
        $supplier = $this->supplierService->updateSupplier($supplier, $validated);
        return response()->json(["message" => "Supplier Updated Successfully!", "supplier" => $supplier]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(["message" => "Deleted Supplier Successfully!"]);
    }
}
