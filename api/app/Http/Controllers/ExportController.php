<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    /**
     * Longest span a single export may cover.
     *
     * Exports are built in memory and streamed back synchronously, so the range is
     * also a memory bound: nothing capped `date_from`/`date_to`, and both reached
     * `whereDate()` unchecked, so a request spanning the whole history of a mature
     * tenant materialised every sale, purchase and product in one array. The date
     * columns are also never validated, so a malformed value became a Postgres cast
     * error and a 500 rather than a 422.
     *
     * A year is chosen to cover the ordinary "last financial year" export. Anything
     * larger belongs in the queued ReportExport path, which exists for exactly that.
     */
    private const MAX_RANGE_DAYS = 366;

    public function __construct(protected ExportService $exportService) {}

    public function export(Request $request, string $type)
    {
        $validated = $request->validate([
            'format' => 'nullable|string|in:csv,xlsx',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if (! empty($validated['date_from']) && ! empty($validated['date_to'])) {
            $from = Carbon::parse($validated['date_from'])->startOfDay();
            $to = Carbon::parse($validated['date_to'])->startOfDay();
            $days = $from->diffInDays($to);

            if ($days > self::MAX_RANGE_DAYS) {
                abort(422, sprintf(
                    'An export may cover at most %d days. Requested %d. Use a report export for a longer range.',
                    self::MAX_RANGE_DAYS,
                    $days
                ));
            }
        }

        $filters = array_filter([
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ], static fn ($value) => $value !== null);

        $format = $validated['format'] ?? 'csv';

        return $this->exportService->export($type, $filters, $format);
    }
}
