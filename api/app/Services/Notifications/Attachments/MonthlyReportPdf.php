<?php

namespace App\Services\Notifications\Attachments;

use App\Contracts\Notifications\AttachmentBuilder;
use App\Models\NotificationDelivery;
use App\ValueObjects\MonthlyReport;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * The monthly performance report as a PDF.
 *
 * Rendered entirely from the delivery's stored payload, with no database reads. The
 * figures were computed by whatever triggered the notification and frozen into the
 * delivery row when it was reserved; re-querying them at send time would produce a
 * document describing a different month from the one summarised in the email body
 * beside it, and the two would disagree on any sale that landed in between.
 *
 * The per-branch table is included when the payload carries one and omitted when it
 * does not, because whether the monthly report is consolidated or per-branch is still an
 * open decision (undone.md, #6) and this should not pre-empt it.
 */
class MonthlyReportPdf implements AttachmentBuilder
{
    public function name(): string
    {
        return 'monthly_report_pdf';
    }

    public function build(NotificationDelivery $delivery): ?array
    {
        $report = MonthlyReport::fromPayload($delivery->values());

        if (! $report->hasFigures()) {
            return null;
        }

        $pdf = Pdf::loadView('pdfs.monthly-report', ['report' => $report->toArray()]);

        return [
            'filename' => 'monthly-report-'.($report->slug() ?: 'report').'.pdf',
            'content' => $pdf->output(),
            'mime' => 'application/pdf',
        ];
    }
}
