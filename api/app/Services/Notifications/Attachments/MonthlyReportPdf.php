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
 * Reports are per branch: one document describes one branch's month, and the branch is
 * named in the layout's header. The branch table is rendered only when a payload carries
 * more than one row, which in practice is never — it survives so that a payload from a
 * trigger that predates per-branch reporting still renders rather than losing figures.
 *
 * render() is public so that the executive reports download and the email attachment go
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
     * The download filename for a report, e.g. "monthly-report-kampala-august-2026.pdf".
     *
     * The branch is in the name because the branch is now the document's subject. Without
     * it, two branches of one business downloading the same month land in a downloads
     * folder as two identically named files, and the one you did not mean is the one you
     * open.
     *
     * The empty branch case is preserved because a payload predating per-branch
     * reporting carries no branch_name, and a nameless file is better than a file that
     * claims to be about a branch that was never named.
     */
    public function filename(MonthlyReport $report): string
    {
        $branch = $report->branchSlug();
        $period = $report->slug();

        $parts = array_filter([$branch ?: null, $period ?: null]) ?: ['report'];

        return 'monthly-report-'.implode('-', $parts).'.pdf';
    }
}
