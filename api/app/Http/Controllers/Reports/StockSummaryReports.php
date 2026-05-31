<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\StockSummaryReports as StockSummaryReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockSummaryReports extends Controller
{
    public function __construct(protected StockSummaryReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $report = $this->service->stockSummary($request->all(), Auth::user(), $perPage);

        return response()->json([
            'message' => 'Stock summary report fetched',
            'data' => $report,
        ], 200);
    }
}
