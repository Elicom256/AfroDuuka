<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\DeadStockReports as DeadStockReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeadStockReports extends Controller
{
    public function __construct(protected DeadStockReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $params = $request->all();
        if ($request->has('period') && !$request->has('filter')) {
            $params['filter'] = $request->input('period');
        }
        $report = $this->service->deadStock($params, Auth::user());

        return response()->json([
            'message' => 'Dead stock report fetched',
            'data' => $report,
        ], 200);
    }
}
