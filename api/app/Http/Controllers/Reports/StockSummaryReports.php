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
        $params = $request->all();
        if ($request->has('period') && !$request->has('filter')) {
            $params['filter'] = $request->input('period');
        }
        $report = $this->service->stockSummary($params, Auth::user(), $perPage);

        return response()->json([
            'message' => 'Stock summary report fetched',
            'data' => $report,
        ], 200);
    }
}
