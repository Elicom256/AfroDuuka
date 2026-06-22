<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\StockMovementReports as StockMovementReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockMovementReports extends Controller
{
    public function __construct(protected StockMovementReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $params = $request->all();
        if ($request->has('period') && !$request->has('filter')) {
            $params['filter'] = $request->input('period');
        }
        $report = $this->service->stockMovements($params, Auth::user());

        return response()->json([
            'message' => 'Stock movement report fetched',
            'data' => $report,
        ], 200);
    }
}
