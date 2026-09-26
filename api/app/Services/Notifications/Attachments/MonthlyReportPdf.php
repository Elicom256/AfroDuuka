<?php

namespace App\Services\Notifications\Attachments;

use App\Contracts\Notifications\AttachmentBuilder;
use App\Models\NotificationDelivery;
use App\ValueObjects\MonthlyReport;
use Barryvdh\DomPDF\Facade\Pdf;
// Aliased, because PHP class names are case-insensitive: importing the concrete
// document as PDF alongside the Pdf facade collides, and the resulting "name is
// already in use" is a fatal at class-compile time, which PHPUnit reports as
// "Premature end of PHP process" rather than as anything pointing at this file.
use Barryvdh\DomPDF\PDF as DomPdf;

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
 *
 * render() is public so that the admin reports download and the email attachment go
 * through the same renderer and the same view. A second copy of this layout is the way
 * the downloadable PDF and the emailed PDF end up disagreeing.
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

        return [
            'filename' => $this->filename($report),
            'content' => $this->render($report)->output(),
            'mime' => 'application/pdf',
        ];
    }

    /**
     * Render a report to a PDF document.
     */
    public function render(MonthlyReport $report): DomPdf
    {
        return Pdf::loadView('pdfs.monthly-report', ['report' => $report->toArray()]);
    }

    /**
     * The download filename for a report, e.g. "monthly-report-august-2026.pdf".
     */
    public function filename(MonthlyReport $report): string
    {
        return 'monthly-report-'.($report->slug() ?: 'report').'.pdf';
    }
}
