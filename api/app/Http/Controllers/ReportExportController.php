<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportExportRequest;
use App\Http\Requests\UpdateReportExportRequest;
use App\Models\ReportExport;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

        ActivityLog::create([
            'log_name' => 'data_export',
            'description' => 'Report export requested',
            'subject_type' => ReportExport::class,
            'subject_id' => $export->id,
            'causer_type' => User::class,
            'causer_id' => Auth::id(),
            'properties' => [
                'attributes' => [
                    'report_type' => $export->report_type,
                    'format' => $export->format,
                    'status' => $export->status,
                ],
            ],
        ]);

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
