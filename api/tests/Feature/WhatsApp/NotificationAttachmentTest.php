<?php

namespace Tests\Feature\WhatsApp;

use App\Contracts\Notifications\AttachmentBuilder;
use App\Jobs\SendNotificationJob;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\NotificationDelivery;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\Notifications\AttachmentRegistry;
use App\Services\Notifications\ChannelRegistry;
use App\Services\Notifications\NotificationCatalogue;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\TemplateProvisioner;
use App\ValueObjects\MonthlyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Tests\TestCase;

/**
 * Exercises PDF attachments through the `array` mailer, so every assertion is against
 * the MIME message SES would actually receive.
 *
 * Not Mail::fake(), and not an assertion that a builder returned bytes: the thing worth
 * proving is that the file survives the whole path into a real multipart message with a
 * real filename and a real media type, because every step of that is a place the
 * attachment can be silently downgraded to a text/plain blob that a customer opens to
 * find unreadable.
 */
class NotificationAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create([
            'name' => 'Acme Stores',
            'phone' => '+256700000001',
            'email' => 'owner@example.test',
        ]);

        app(TemplateProvisioner::class)->ensureForBusiness($this->business->id);

        $this->transport()->flush();
    }

    private function transport()
    {
        return app('mailer')->getSymfonyTransport();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function deliver(string $type, array $values): NotificationDelivery
    {
        Queue::fake();

        app(NotificationDispatcher::class)->dispatch(
            $type,
            $this->business->id,
            $values + ['business_id' => $this->business->id],
            $values,
            null,
            ['email'],
            'owner@example.test',
        );

        return NotificationDelivery::withoutGlobalScopes()
            ->where('type', $type)
            ->where('channel', 'email')
            ->latest('id')
            ->firstOrFail();
    }

    private function send(NotificationDelivery $delivery): NotificationDelivery
    {
        $this->transport()->flush();

        (new SendNotificationJob($delivery->id, 'email'))->handle(app(ChannelRegistry::class));

        return $delivery->fresh();
    }

    private function sentEmail(): Email
    {
        $messages = $this->transport()->messages();

        $this->assertCount(1, $messages, 'Expected exactly one email to have been sent.');

        return $messages->first()->getOriginalMessage();
    }

    /**
     * The PDF parts of the sent message, and nothing else.
     *
     * @return array<int, DataPart>
     */
    private function pdfAttachments(Email $email): array
    {
        return array_values(array_filter(
            $email->getAttachments(),
            fn (DataPart $part) => $part->getMediaType() === 'application'
                && $part->getMediaSubtype() === 'pdf'
        ));
    }

    /**
     * A full set of monthly figures, as the trigger would supply them.
     *
     * @return array<string, mixed>
     */
    private function reportValues(array $overrides = []): array
    {
        return $overrides + [
            'period' => 'August 2026',
            'business_name' => 'Acme Stores',
            'currency' => 'UGX',
            'total_sales' => '4,250,000.00',
            'total_purchases' => '1,900,000.00',
            'total_expenses' => '350,000.00',
            'total_profit_loss' => '2,000,000.00',
            'number_of_sales' => 312,
            'number_of_purchases' => 41,
        ];
    }

    private function quotation(): Quotation
    {
        $branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Acme Kampala',
        ]);

        $quotation = Quotation::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $branch->id,
            'quotation_number' => 'QT-2026-0042',
            'status' => 'sent',
            'currency' => 'UGX',
            'subtotal' => 100000,
            'tax_amount' => 18000,
            'discount' => 0,
            'total_amount' => 118000,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => Product::factory()->create([
                'business_branch_id' => $branch->id,
            ])->id,
            'product_name' => 'Maize Flour 50kg',
            'quantity' => 20,
            'unit_price' => 5000,
            'discount' => 0,
            'subtotal' => 100000,
        ]);

        return $quotation->fresh();
    }

    public function test_the_monthly_report_email_carries_a_pdf(): void
    {
        $delivery = $this->deliver('report.monthly', $this->reportValues());

        $fresh = $this->send($delivery);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);

        $attachments = $this->pdfAttachments($this->sentEmail());

        $this->assertCount(1, $attachments);
        $this->assertSame('monthly-report-august-2026.pdf', $attachments[0]->getFilename());
        $this->assertStringStartsWith('%PDF-', $attachments[0]->getBody());
    }

    public function test_the_quotation_email_carries_a_pdf(): void
    {
        $quotation = $this->quotation();

        $delivery = $this->deliver('quotation.sent', [
            'quotation_id' => $quotation->id,
            'version' => 1,
            'quotation_number' => $quotation->quotation_number,
            'total' => 'UGX 118,000',
        ]);

        $fresh = $this->send($delivery);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);

        $attachments = $this->pdfAttachments($this->sentEmail());

        $this->assertCount(1, $attachments);
        $this->assertSame('quotation-QT-2026-0042.pdf', $attachments[0]->getFilename());
        $this->assertStringStartsWith('%PDF-', $attachments[0]->getBody());
    }

    public function test_the_attached_report_is_a_real_document_and_not_a_stub(): void
    {
        $delivery = $this->deliver('report.monthly', $this->reportValues());

        $this->send($delivery);

        $body = $this->pdfAttachments($this->sentEmail())[0]->getBody();

        // A PDF that rendered an empty page is still a syntactically valid PDF, so the
        // magic bytes alone would pass a file that reached the customer as a blank sheet.
        // Dompdf emits one indirect object per page, so a real render clears this.
        $this->assertGreaterThan(
            2000,
            strlen($body),
            'The report PDF is too small to be a rendered document.'
        );
        $this->assertSame(
            1,
            preg_match('/\/Type\s*\/Page[^s]/', $body),
            'The report PDF contains no page object.'
        );
    }

    public function test_a_thousands_separated_figure_is_not_read_as_its_leading_digit(): void
    {
        // number_format() output is what the payload really carries. Cast naively it
        // becomes 4.0 and the report understates a year of revenue by a factor of a
        // million, with nothing anywhere reporting an error.
        $report = MonthlyReport::fromPayload($this->reportValues());

        $this->assertSame(4250000.0, $report->figures['sales']);
        $this->assertSame(1900000.0, $report->figures['purchases']);
        $this->assertSame(350000.0, $report->figures['expenses']);
        $this->assertSame(2000000.0, $report->figures['profit_loss']);
        $this->assertSame(312, $report->counts['sales']);
        $this->assertSame(41, $report->counts['purchases']);
    }

    public function test_the_payload_keys_the_report_expects_are_the_ones_documented(): void
    {
        // The trigger that fills this payload does not exist yet (Stage 4). Until it
        // does, this is the only thing standing between a key rename and a report that
        // silently prints "not recorded" where the profit should be.
        $expected = [
            'business_name', 'period', 'currency',
            'total_sales', 'total_purchases', 'total_expenses', 'total_profit_loss',
            'number_of_sales', 'number_of_purchases', 'branches',
        ];

        $this->assertEqualsCanonicalizing(
            $expected,
            array_keys($this->reportValues(['branches' => []]))
        );

        // And every one of them has to actually reach the report. A payload key that
        // is accepted but never read is the same failure as one that is misspelt.
        $report = MonthlyReport::fromPayload($this->reportValues(['branches' => [
            ['name' => 'Kampala', 'sales' => 1.0],
        ]]));

        $this->assertSame('Acme Stores', $report->businessName);
        $this->assertSame('August 2026', $report->period);
        $this->assertSame('UGX', $report->currency);
        $this->assertCount(1, $report->branches);
    }

    public function test_a_report_with_no_figures_is_not_attached(): void
    {
        // The body already says the report is ready, so an empty table attached to it
        // reads as a broken file rather than as a month with nothing in it.
        $delivery = $this->deliver('report.monthly', [
            'period' => 'August 2026',
            'report_url' => 'https://example.test/reports/aug',
        ]);

        $fresh = $this->send($delivery);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertCount(0, $this->pdfAttachments($this->sentEmail()));
    }

    public function test_a_quotation_that_no_longer_exists_does_not_stop_the_email(): void
    {
        $delivery = $this->deliver('quotation.sent', [
            'quotation_id' => 999999,
            'version' => 1,
            'quotation_number' => 'QT-2026-9999',
            'total' => 'UGX 118,000',
        ]);

        $fresh = $this->send($delivery);

        // The customer still gets the notification; they just do not get a file that
        // cannot be produced.
        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertCount(0, $this->pdfAttachments($this->sentEmail()));
        $this->assertStringContainsString(
            'QT-2026-9999',
            (string) $this->sentEmail()->getHtmlBody()
        );
    }

    public function test_a_quotation_belonging_to_another_business_is_not_attached(): void
    {
        $theirs = $this->quotation();

        $other = Business::factory()->create([
            'name' => 'Someone Else',
            'email' => 'else@example.test',
        ]);

        app(TemplateProvisioner::class)->ensureForBusiness($other->id);

        $foreign = NotificationDelivery::withoutGlobalScopes()->create([
            'business_id' => $other->id,
            'channel' => 'email',
            'category' => 'order',
            'type' => 'quotation.sent',
            'recipient_address' => 'owner@example.test',
            'status' => NotificationDelivery::STATUS_PENDING,
            'dedupe_key' => 'foreign-tenant-quotation-probe',
            'payload' => [
                'quotation_id' => $theirs->id,
                'quotation_number' => $theirs->quotation_number,
            ],
        ]);

        $this->send($foreign);

        // A quotation id in a delivery row is not on its own proof the row belongs to
        // that business. Without the tenant context around the lookup this would attach
        // one business's priced quotation — with its customer, its line items and its
        // totals — to another business's notification.
        $this->assertSame(NotificationDelivery::STATUS_SENT, $foreign->fresh()->status);
        $this->assertCount(0, $this->pdfAttachments($this->sentEmail()));
    }

    public function test_an_email_with_no_declared_attachment_sends_none(): void
    {
        $delivery = $this->deliver('registration.welcome', [
            'business_name' => 'Acme Stores',
            'trial_ends_at' => '30 Sep 2026',
        ]);

        $fresh = $this->send($delivery);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertCount(0, $this->pdfAttachments($this->sentEmail()));
    }

    /**
     * Add an attachment declaration to a catalogue entry.
     *
     * The whole catalogue is written back rather than a dotted path, because the keys
     * are type names that themselves contain dots: `config()->set('...registration.
     * welcome.attachments', …)` traverses into `registration`, does not find it, and
     * replaces the entry with a nested array that then fails validation.
     *
     * @param  array<int, string>  $names
     */
    private function declareAttachments(string $type, array $names): void
    {
        $catalogue = config('notifications.catalogue');

        $catalogue[$type]['attachments'] = $names;

        config()->set('notifications.catalogue', $catalogue);
    }

    public function test_an_attachment_that_throws_does_not_fail_the_send(): void
    {
        $this->declareAttachments('registration.welcome', ['exploding_pdf']);

        $this->app->bind(
            AttachmentRegistry::class,
            fn () => new AttachmentRegistry($this->app, ['exploding_pdf' => ExplodingAttachment::class])
        );

        $delivery = $this->deliver('registration.welcome', [
            'business_name' => 'Acme Stores',
            'trial_ends_at' => '30 Sep 2026',
        ]);

        $fresh = $this->send($delivery);

        // A Blade typo in a PDF view must not cost the customer the email. The body
        // already carries the content; the file was only ever a convenience.
        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertCount(0, $this->pdfAttachments($this->sentEmail()));
        $this->assertStringContainsString(
            'Your business is live',
            (string) $this->sentEmail()->getHtmlBody()
        );
    }

    public function test_an_unknown_attachment_name_does_not_fail_the_send(): void
    {
        $this->declareAttachments('registration.welcome', ['no_such_builder']);

        $delivery = $this->deliver('registration.welcome', [
            'business_name' => 'Acme Stores',
            'trial_ends_at' => '30 Sep 2026',
        ]);

        $fresh = $this->send($delivery);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $fresh->status);
        $this->assertCount(0, $this->pdfAttachments($this->sentEmail()));
    }

    public function test_every_catalogue_attachment_name_resolves_to_a_builder(): void
    {
        $available = app(AttachmentRegistry::class)->available();

        // A typo here would otherwise be invisible until a customer opened an email and
        // found no file in it.
        foreach (app(NotificationCatalogue::class)->all() as $type => $entry) {
            foreach ($entry['attachments'] ?? [] as $name) {
                $this->assertContains(
                    $name,
                    $available,
                    "Catalogue entry \"{$type}\" names an attachment that does not exist."
                );
            }
        }
    }

    public function test_only_report_and_quotation_carry_a_pdf(): void
    {
        $withAttachments = [];

        foreach (app(NotificationCatalogue::class)->all() as $type => $entry) {
            if (($entry['attachments'] ?? []) !== []) {
                $withAttachments[] = $type;
            }
        }

        // If the product adds a third document-shaped notification, this fails rather
        // than letting it ship with a body but no document.
        $this->assertEqualsCanonicalizing(
            ['report.monthly', 'quotation.sent'],
            $withAttachments
        );
    }

    public function test_an_attachment_on_a_notification_that_never_sends_email_is_rejected(): void
    {
        $this->declareAttachments('order.purchase', ['quotation_pdf']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('declares attachments but never sends email');

        app(NotificationCatalogue::class)->all();
    }

    public function test_a_branch_breakdown_is_rendered_when_the_payload_carries_one(): void
    {
        $values = $this->reportValues([
            'branches' => [
                ['name' => 'Kampala', 'sales' => '2,000,000.00', 'profit_loss' => '900,000.00'],
                ['name' => 'Jinja', 'sales' => '2,250,000.00', 'profit_loss' => '-120,000.00'],
            ],
        ]);

        $report = MonthlyReport::fromPayload($values);

        $this->assertCount(2, $report->branches);
        $this->assertSame('Kampala', $report->branches[0]['name']);
        $this->assertSame(2000000.0, $report->branches[0]['sales']);

        // A loss stays negative in the data so the view can label it, rather than being
        // flattened to an absolute value that reads as a profit.
        $this->assertSame(-120000.0, $report->branches[1]['profit_loss']);

        $delivery = $this->deliver('report.monthly', $values);

        $this->assertSame(NotificationDelivery::STATUS_SENT, $this->send($delivery)->status);
        $this->assertCount(1, $this->pdfAttachments($this->sentEmail()));
    }
}

/**
 * A builder that always fails, to prove the send survives it.
 */
class ExplodingAttachment implements AttachmentBuilder
{
    public function name(): string
    {
        return 'exploding_pdf';
    }

    public function build(NotificationDelivery $delivery): ?array
    {
        throw new \RuntimeException('PDF view is missing.');
    }
}
