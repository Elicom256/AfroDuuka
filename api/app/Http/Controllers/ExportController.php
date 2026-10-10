<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(protected ExportService $exportService) {}

    public function export(Request $request, string $type)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $format = $request->string('format', 'csv')->toString();

        return $this->exportService->export($type, $filters, $format);
    }
}
