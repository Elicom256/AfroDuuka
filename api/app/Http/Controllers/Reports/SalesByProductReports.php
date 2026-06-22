<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\SalesByProductReports as SalesByProductReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesByProductReports extends Controller
{
    public function __construct(protected SalesByProductReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $params = $request->all();
        if ($request->has('period') && !$request->has('filter')) {
            $params['filter'] = $request->input('period');
        }
        $report = $this->service->salesByProduct($params, Auth::user());

        return response()->json([
            'message' => 'Sales by product report fetched',
            'data' => $report,
        ], 200);
    }
}
