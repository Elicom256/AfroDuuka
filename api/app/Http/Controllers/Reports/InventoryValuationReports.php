<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\InventoryValuationReports as InventoryValuationReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryValuationReports extends Controller
{
    public function __construct(protected InventoryValuationReportsService $service)
    {
    }

    public function index(Request $request)
    {
        $params = $request->all();
        if ($request->has('period') && !$request->has('filter')) {
            $params['filter'] = $request->input('period');
        }
        $report = $this->service->inventoryValuation($params, Auth::user());

        return response()->json([
            'message' => 'Inventory valuation report fetched',
            'data' => $report,
        ], 200);
    }
}
