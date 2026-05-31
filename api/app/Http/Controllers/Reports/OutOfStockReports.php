<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\OutOfStockReports as OutOfStockReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OutOfStockReports extends Controller
{
    public function __construct(protected OutOfStockReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $report = $this->service->outOfStock($request->all(), Auth::user());

        return response()->json([
            'message' => 'Out of stock report fetched',
            'data' => $report,
        ], 200);
    }
}
