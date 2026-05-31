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
        $report = $this->service->inventoryValuation($request->all(), Auth::user());

        return response()->json([
            'message' => 'Inventory valuation report fetched',
            'data' => $report,
        ], 200);
    }
}
