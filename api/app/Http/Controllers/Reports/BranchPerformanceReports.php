<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\BranchPerformanceReports as BranchPerformanceReportsService;
use App\Support\Tenant\EffectiveBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchPerformanceReports extends Controller
{
    public function __construct(protected BranchPerformanceReportsService $service) {}

    public function index(Request $request)
    {
        $filter = $request->input('filter', $request->input('period', 'this_month'));

        // The dashboard has always sent ?id=<branch> from its branch dropdown. It was
        // dropped on the floor here, so the selector narrowed nothing and the card
        // silently showed every branch whatever the user picked.
        //
        // Absent is still meaningful here and means "all branches I can see", because
        // this card exists to compare them. Only a branch that was actually named has
        // to be resolved and authorised; that is a different question from which
        // branch a per-branch report should default to.
        $branchId = null;

        if ($request->filled('id')) {
            $branchId = EffectiveBranchScope::resolveReportBranch(Auth::user(), $request->input('id'));
        }

        $report = $this->service->branchPerformance($filter, Auth::user(), $branchId);

        return response()->json([
            'message' => 'Branch performance report fetched',
            'data' => $report,
        ], 200);
    }
}
