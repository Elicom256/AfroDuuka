<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\LowStockReports as LowStockReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LowStockReports extends Controller
{
    public function __construct(protected LowStockReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $params = $request->all();
        if ($request->has('period') && !$request->has('filter')) {
            $params['filter'] = $request->input('period');
        }
        $report = $this->service->lowStock($params, Auth::user());

        return response()->json([
            'message' => 'Low stock report fetched',
            'data' => $report,
        ], 200);
    }
}
