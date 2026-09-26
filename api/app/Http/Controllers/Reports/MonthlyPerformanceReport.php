<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Services\Notifications\Attachments\MonthlyReportPdf;
use App\Services\Reports\MonthlyPerformanceReport as MonthlyPerformanceReportService;
use App\Support\Tenant\EffectiveBranchScope;
use App\ValueObjects\MonthlyReport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MonthlyPerformanceReport extends Controller
{
    public function __construct(
        protected MonthlyPerformanceReportService $service,
        protected MonthlyReportPdf $pdf,
    ) {}

    /**
     * The figures behind the report, for the dashboard card.
     */
    public function index(Request $request): JsonResponse
    {
        [$business, $branch, $month] = $this->resolve($request);

        $report = MonthlyReport::fromPayload($this->service->payload($business, $branch, $month));

        return response()->json([
            'message' => 'Monthly performance report fetched',
            'data' => $report->toArray() + [
                'month' => $month->format('Y-m'),
                'branch_id' => $branch->id,
            ],
        ], 200);
    }

    /**
     * The report as a downloadable PDF.
     *
     * Computed on demand rather than read back from a stored notification delivery: the
     * email is a snapshot of the month as it was summarised, but a person asking for the
     * document from the dashboard wants the figures as they stand.
     *
     * Returned as a binary attachment rather than base64'd into JSON the way the
     * quotation preview does. That endpoint returns JSON because it is feeding a preview
     * component that wants the bytes in the store; a download has no such need, and
     * base64 would inflate the payload by a third and cap out on large reports.
     */
    public function pdf(Request $request): Response
    {
        [$business, $branch, $month] = $this->resolve($request);

        $report = MonthlyReport::fromPayload($this->service->payload($business, $branch, $month));

        return $this->pdf->render($report)->download($this->pdf->filename($report));
    }

    /**
     * @return array{0: Business, 1: BusinessBranch, 2: Carbon}
     *
     * @throws ValidationException
     */
    private function resolve(Request $request): array
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = Auth::user();
        $business = $user?->business()->first();

        abort_if($business === null, 403, 'No business is associated with this account.');

        // Authorised before it is read, not after: a branch id that is not the caller's
        // has to fail even when it happens to share an id with nothing they own.
        $branchId = EffectiveBranchScope::resolveReportBranch($user, $request->input('branch_id'));

        $branch = BusinessBranch::query()
            ->where('business_id', $business->id)
            ->findOrFail($branchId);

        $month = $request->filled('month')
            ? Carbon::createFromFormat('!Y-m', (string) $request->input('month'))->startOfMonth()
            : $this->lastCompletedMonth($business);

        return [$business, $branch, $month];
    }

    /**
     * The most recent month that has finished, in the business's own timezone.
     *
     * The current month is deliberately not the default. It is still accumulating
     * transactions, so its totals understate the month and a report labelled with it
     * would look like a fall in trade rather than an unfinished period.
     */
    private function lastCompletedMonth(Business $business): Carbon
    {
        return Carbon::now($business->timezone ?: config('app.timezone'))
            ->subMonthNoOverflow()
            ->startOfMonth();
    }
}
