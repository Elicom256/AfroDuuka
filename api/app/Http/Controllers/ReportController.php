<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Report;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * BaseModel's business scope keeps this to the caller's tenant; ReportPolicy
     * grants read to every tenant user and write to elevated roles only.
     */
    public function index()
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::orderByDesc('created_at')->get();

        return response()->json(['message' => 'All reports fetched', 'reports' => $reports]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportRequest $request)
    {
        $this->authorize('create', Report::class);

        $report = Report::create($request->validated());

        return response()->json(['message' => 'Report created', 'report' => $report], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        $this->authorize('view', $report);

        return response()->json(['message' => 'Report fetched', 'report' => $report]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReportRequest $request, Report $report)
    {
        $this->authorize('update', $report);

        $report->update($request->validated());

        return response()->json(['message' => 'Report updated', 'report' => $report]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        $this->authorize('delete', $report);

        $report->delete();

        return response()->json(['message' => 'Report deleted']);
    }
}
