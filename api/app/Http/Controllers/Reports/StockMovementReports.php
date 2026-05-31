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
        $report = $this->service->stockMovements($request->all(), Auth::user());

        return response()->json([
            'message' => 'Stock movement report fetched',
            'data' => $report,
        ], 200);
    }
}
