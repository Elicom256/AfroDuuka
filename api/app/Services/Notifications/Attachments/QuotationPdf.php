<?php

namespace App\Services\Notifications\Attachments;

use App\Contracts\Notifications\AttachmentBuilder;
use App\Models\NotificationDelivery;
use App\Models\Quotation;
use App\Support\Tenant\BusinessContext;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * The customer's copy of a sent quotation, as a PDF.
 *
 * Reuses the `pdfs.quotation` view the download endpoint already renders, rather than
 * a second template written for email. Two templates for one document means the emailed
 * quotation and the downloaded one drift, and the copy the customer attaches to a
 * purchase order is the emailed one.
 */
class QuotationPdf implements AttachmentBuilder
{
    public function name(): string
    {
        return 'quotation_pdf';
    }

    public function build(NotificationDelivery $delivery): ?array
    {
        $quotationId = $delivery->values()['quotation_id'] ?? null;

        if (! is_numeric($quotationId)) {
            return null;
        }

        // Inside the delivery's own tenant. The payload names a row by id, and an id
        // from a delivery row is not on its own proof the row belongs to that
        // business — running the lookup in context makes the global scopes constrain
        // it rather than trusting the payload.
        $quotation = app(BusinessContext::class)->run(
            $delivery->business_id,
            fn () => Quotation::query()
                ->with(['items.product', 'customer', 'user', 'businessBranch'])
                ->find((int) $quotationId)
        );

        // Deleted, or never existed, or belongs to another business. All three are the
        // same outcome here: the email still goes out with its summary in the body.
        if ($quotation === null) {
            return null;
        }

        $pdf = Pdf::loadView('pdfs.quotation', compact('quotation'));

        return [
            'filename' => "quotation-{$quotation->quotation_number}.pdf",
            'content' => $pdf->output(),
            'mime' => 'application/pdf',
        ];
    }
}
