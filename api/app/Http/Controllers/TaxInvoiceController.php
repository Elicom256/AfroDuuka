<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxInvoiceRequest;
use App\Http\Requests\UpdateTaxInvoiceRequest;
use App\Models\TaxInvoice;

/**
 * Manages URA-compliant tax invoices linked to sales (EFRIS preparation).
 */
class TaxInvoiceController extends Controller
{
    public function index()
    {
        $invoices = TaxInvoice::where('business_id', auth()->user()->business_id)
            ->with(['sale', 'businessBranch'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['message' => 'Fetched tax invoices', 'data' => $invoices]);
    }

    public function store(StoreTaxInvoiceRequest $request)
    {
        $invoice = TaxInvoice::create($request->validated());
        return response()->json(['message' => 'Tax invoice created', 'data' => $invoice], 201);
    }

    public function show(TaxInvoice $taxInvoice)
    {
        $taxInvoice->load(['sale', 'businessBranch']);
        return response()->json(['message' => 'Fetched tax invoice', 'data' => $taxInvoice]);
    }

    public function update(UpdateTaxInvoiceRequest $request, TaxInvoice $taxInvoice)
    {
        $taxInvoice->update($request->validated());
        return response()->json(['message' => 'Tax invoice updated', 'data' => $taxInvoice]);
    }

    /**
     * Mark invoice as submitted to URA EFRIS.
     */
    public function submitToUra(TaxInvoice $taxInvoice)
    {
        $taxInvoice->update([
            'submitted_to_ura' => true,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        return response()->json(['message' => 'Tax invoice submitted to URA', 'data' => $taxInvoice]);
    }

    public function destroy(TaxInvoice $taxInvoice)
    {
        $taxInvoice->delete();
        return response()->json(['message' => 'Tax invoice deleted']);
    }
}
