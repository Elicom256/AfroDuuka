<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportExportRequest;
use App\Http\Requests\UpdateReportExportRequest;
use App\Models\ActivityLog;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\Auth\RolePermissions;
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
        // An export pulls data out of the business in a form that leaves the tenant
        // scope entirely, so it follows canManageReports() — the same capability
        // ReportPolicy::create already enforces over the report definitions
        // themselves. Note that means Executive only, not BranchManager: exporting is
        // a business-level read.
        abort_unless(RolePermissions::canManageReports($request->user()), 403, 'You cannot request report exports.');

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
        abort_unless(RolePermissions::canManageReports($request->user()), 403, 'You cannot change report exports.');

        $reportExport->update($request->validated());

        return response()->json(['message' => 'Report export updated', 'data' => $reportExport]);
    }

    public function destroy(ReportExport $reportExport)
    {
        $reportExport->delete();

        return response()->json(['message' => 'Report export deleted']);
    }
}
