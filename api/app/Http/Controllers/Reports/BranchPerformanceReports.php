<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\BranchPerformanceReports as BranchPerformanceReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchPerformanceReports extends Controller
{
    public function __construct(protected BranchPerformanceReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $filter = $request->input('filter', $request->input('period', 'this_month'));
        $report = $this->service->branchPerformance($filter, Auth::user());

        return response()->json([
            'message' => 'Branch performance report fetched',
            'data' => $report,
        ], 200);
    }
}
