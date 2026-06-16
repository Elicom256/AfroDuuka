<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrinterRequest;
use App\Http\Requests\UpdatePrinterRequest;
use App\Models\Printer;

/**
 * Manages thermal receipt printers per branch.
 */
class PrinterController extends Controller
{
    public function index()
    {
        $printers = Printer::where('business_id', auth()->user()->business_id)->get();
        return response()->json(['message' => 'Fetched printers', 'data' => $printers]);
    }

    public function store(StorePrinterRequest $request)
    {
        $printer = Printer::create($request->validated());

        if ($printer->is_default) {
            Printer::where('business_branch_id', $printer->business_branch_id)
                ->where('id', '!=', $printer->id)
                ->update(['is_default' => false]);
        }

        return response()->json(['message' => 'Printer created', 'data' => $printer], 201);
    }

    public function show(Printer $printer)
    {
        return response()->json(['message' => 'Fetched printer', 'data' => $printer]);
    }

    public function update(UpdatePrinterRequest $request, Printer $printer)
    {
        $printer->update($request->validated());

        if ($printer->is_default) {
            Printer::where('business_branch_id', $printer->business_branch_id)
                ->where('id', '!=', $printer->id)
                ->update(['is_default' => false]);
        }

        return response()->json(['message' => 'Printer updated', 'data' => $printer]);
    }

    public function destroy(Printer $printer)
    {
        $printer->delete();
        return response()->json(['message' => 'Printer deleted']);
    }
}
