<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportExportRequest;
use App\Http\Requests\UpdateReportExportRequest;
use App\Models\ReportExport;

/**
 * Manages async report export requests (CSV, XLSX, PDF).
 */
class ReportExportController extends Controller
{
    public function index()
    {
        $exports = ReportExport::where('business_id', auth()->user()->business_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['message' => 'Fetched report exports', 'data' => $exports]);
    }

    public function store(StoreReportExportRequest $request)
    {
        $export = ReportExport::create($request->validated());
        return response()->json(['message' => 'Report export queued', 'data' => $export], 201);
    }

    public function show(ReportExport $reportExport)
    {
        return response()->json(['message' => 'Fetched report export', 'data' => $reportExport]);
    }

    public function update(UpdateReportExportRequest $request, ReportExport $reportExport)
    {
        $reportExport->update($request->validated());
        return response()->json(['message' => 'Report export updated', 'data' => $reportExport]);
    }

    public function destroy(ReportExport $reportExport)
    {
        $reportExport->delete();
        return response()->json(['message' => 'Report export deleted']);
    }
}
