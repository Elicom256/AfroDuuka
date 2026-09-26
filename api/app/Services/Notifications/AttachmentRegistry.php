<?php

namespace App\Services\Notifications;

use App\Contracts\Notifications\AttachmentBuilder;
use App\Models\NotificationDelivery;
use App\Services\Notifications\Attachments\MonthlyReportPdf;
use App\Services\Notifications\Attachments\QuotationPdf;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resolves an attachment name to the builder that produces it, and builds them
 * defensively.
 *
 * The defensiveness is the point. A notification email is the delivery channel for a
 * report the customer has already asked for, and the attachment is a convenience on top
 * of a body that already summarises it. If rendering the PDF throws, letting that
 * propagate would mean the customer receives nothing at all because a Blade view had a
 * typo in it — strictly worse than receiving the email without the file. So a builder
 * that fails is logged and skipped, and the mail goes out regardless.
 *
 * A registry rather than a match statement, mirroring ChannelRegistry, so adding an
 * attachment does not mean editing the catalogue reader and the mailable.
 */
class AttachmentRegistry
{
    /** @var array<string, class-string<AttachmentBuilder>> */
    private const DEFAULTS = [
        'quotation_pdf' => QuotationPdf::class,
        'monthly_report_pdf' => MonthlyReportPdf::class,
    ];

    /** @var array<string, AttachmentBuilder> */
    private array $resolved = [];

    public function __construct(
        private readonly Container $container,
        /** @var array<string, class-string<AttachmentBuilder>> */
        private readonly array $builders = self::DEFAULTS,
    ) {}

    public function for(string $name): ?AttachmentBuilder
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $class = $this->builders[$name] ?? null;

        if ($class === null) {
            return null;
        }

        return $this->resolved[$name] = $this->container->make($class);
    }

    /**
     * Every attachment the catalogue declares for this delivery's type, built.
     *
     * A name that resolves to nothing, or a builder that throws, is dropped rather than
     * raised. See the class docblock for why.
     *
     * @param  array<int, string>  $names
     * @return array<int, array{filename: string, content: string, mime: string}>
     */
    public function buildFor(NotificationDelivery $delivery, array $names): array
    {
        $built = [];

        foreach ($names as $name) {
            $builder = $this->for($name);

            if ($builder === null) {
                // A typo in the catalogue. Silently sending without the file would make
                // this indistinguishable from a builder that legitimately had nothing
                // to attach, so it is worth a log line even though the send continues.
                Log::warning('Unknown notification attachment', [
                    'type' => $delivery->type,
                    'attachment' => $name,
                ]);

                continue;
            }

            try {
                $attachment = $builder->build($delivery);
            } catch (Throwable $e) {
                Log::error('Notification attachment failed to build', [
                    'delivery_id' => $delivery->id,
                    'type' => $delivery->type,
                    'attachment' => $name,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($attachment !== null) {
                $built[] = $attachment;
            }
        }

        return $built;
    }

    /**
     * @return array<int, string>
     */
    public function available(): array
    {
        return array_keys($this->builders);
    }
}
