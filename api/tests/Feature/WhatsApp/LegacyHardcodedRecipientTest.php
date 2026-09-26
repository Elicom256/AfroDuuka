<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The hardcoded number, and the habit that produced it, removed.
 *
 * `+256731794401` appeared as a fallback recipient in the monthly report job, seven
 * listeners and the subscription controller; as a default sending identity in the
 * config, the notification job and the demo config factory; as a column default in the
 * schema, so every config row was born holding it; as an injected value in two form
 * requests; and as the prefilled value of the admin settings form, whose "send demo
 * message" button used it as the recipient. Every one of them meant the same thing: a
 * business with no phone on file had its notifications — subscription expiry, payment
 * failure, sales, purchases — delivered to one developer's personal handset, or
 * appeared to come from it.
 *
 * What made this survivable for so long is that nothing about it looked wrong. The
 * message was composed and sent, the log recorded a recipient, the row said "sent". The
 * only symptom was a stranger's phone quietly filling up with other people's businesses.
 *
 * The new pipeline was never affected: `RecipientResolver` returns null instead of
 * guessing and the channel rejects a delivery with no address. This was a legacy-layer
 * habit, which is a fair argument for retiring that layer rather than patching it
 * forever.
 *
 * The source assertions below are not a substitute for the behavioural ones. They exist
 * because the leak had no behavioural signature: a listener that substituted its own
 * recipient would simply produce a skipped send, which looks exactly like correct
 * behaviour to any test that only checks nothing was delivered.
 */
class LegacyHardcodedRecipientTest extends TestCase
{
    use RefreshDatabase;

    private const HARDCODED = '256731794401';

    public function test_a_notification_with_no_recipient_is_not_queued(): void
    {
        Queue::fake();

        $result = (new WhatsAppNotificationService)->queueBusinessNotification([
            'business_id' => 1,
            'type' => 'subscription.expired',
            'template_key' => 'subscription.expired',
            'recipient_phone' => null,
            'template_data' => ['business_name' => 'Acme'],
        ]);

        Queue::assertNothingPushed();
        $this->assertFalse($result['queued']);
    }

    public function test_a_notification_with_a_recipient_is_still_queued(): void
    {
        Queue::fake();

        $result = (new WhatsAppNotificationService)->queueBusinessNotification([
            'business_id' => 1,
            'type' => 'subscription.expired',
            'template_key' => 'subscription.expired',
            'recipient_phone' => '+256700000123',
            'template_data' => ['business_name' => 'Acme'],
        ]);

        Queue::assertPushed(ProcessWhatsAppNotificationJob::class, 1);
        $this->assertTrue($result['queued']);
    }

    /**
     * The sender itself is now guarded, not only the recipient.
     *
     * Removing the recipient fallbacks while leaving the sending identity defaulted was
     * the half-finished version of this cleanup: the number stopped being a destination
     * and stayed a sender, and a business with no number of its own still had messages
     * attributed to a stranger's handset — which is worse, because the reply goes to
     * them too.
     */
    public function test_the_legacy_number_appears_in_no_shipped_code(): void
    {
        $offenders = [];

        foreach ($this->shippedCodeFiles() as $file) {
            if (str_contains($this->withoutComments($file), self::HARDCODED)) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'The legacy number is back in shipped code. A business with no number of its own must have none, not somebody else\'s.'
        );
    }

    /**
     * Any literal, not just the known one.
     *
     * Naming the number would only catch the number. This catches the next one, which is
     * what actually happened: a value that was convenient in one place and was pasted
     * into eleven. Seeders are excluded because a fixture phone number is test data by
     * definition, and the column default is covered because the migration is swept.
     */
    public function test_no_send_path_invents_a_sending_identity(): void
    {
        $offenders = [];

        foreach ($this->shippedCodeFiles() as $file) {
            if (str_contains($this->relative($file), 'database/seeders')) {
                continue;
            }

            $code = $this->withoutComments($file);

            // `business_phone` assigned a quoted literal, as opposed to read off a config
            // or a model. Both remaining assignments are `config(...)` and
            // `$config->business_phone`, so neither matches.
            if (preg_match("/business_phone'?\"?\s*=>\s*['\"]/", $code)) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'A sending identity is being hardcoded. An unconfigured business has none, and the send path must refuse rather than invent one.'
        );
    }

    /**
     * A recipient must come from a real source, never from a literal.
     *
     * This is deliberately narrower than "no `??` after recipient_phone". Falling back to
     * the business's phone and then the authenticated user's is a real source, and
     * `?? ''` is the safe way to say "none" — the guard in
     * WhatsAppNotificationService relies on exactly that. What is not allowed is a
     * quoted, non-empty string, because that is a number somebody chose once and pasted.
     *
     * Catching the shape rather than the known value is the point: it also catches the
     * next number, which is how this reached eleven files in the first place.
     */
    public function test_no_send_path_substitutes_its_own_recipient(): void
    {
        $offenders = [];

        foreach ($this->shippedCodeFiles() as $file) {
            if (preg_match("/recipient_phone'?\"?\s*=>[^,\n]*\?\?\s*['\"](?!['\"],)/", $this->withoutComments($file))) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders, 'A send path can still substitute its own recipient.');
    }

    /**
     * The monthly report job has never completed a run, and cannot.
     *
     * `purchases` is scoped by business_branch_id and has no business_id column, so
     * `Purchase::where('business_id', ...)` raised an SQL error on the first business and
     * the job fataled. Every scheduled run failed, which is why the hardcoded recipient
     * inside it was latent rather than live. It also computed a company-wide
     * consolidated total — the document shape that was rejected in favour of one report
     * per branch — using Carbon::now() rather than the business timezone, with a dedupe
     * key carrying no branch id. Stage 4 writes the replacement; this asserts only that
     * the broken predecessor stays gone rather than being quietly rescheduled.
     */
    public function test_the_dead_monthly_report_job_stays_deleted(): void
    {
        $this->assertFileDoesNotExist(app_path('Jobs/GenerateMonthlyBusinessReportJob.php'));

        $this->assertStringNotContainsString(
            'GenerateMonthlyBusinessReportJob',
            (string) file_get_contents(base_path('routes/console.php')),
            'The monthly report job is scheduled again. It fatals on the first business, so every run fails.'
        );
    }

    /**
     * Shipped code only: not tests, not vendor, not the compiled front-end bundle.
     *
     * The UI settings component is included by path because it was the one place the
     * number was user-reachable — its "send demo message" button defaulted to it — and a
     * PHP-only sweep would never have looked there.
     *
     * @return array<int, string>
     */
    private function shippedCodeFiles(): array
    {
        $roots = [
            app_path(),
            config_path(),
            base_path('routes'),
            base_path('database/migrations'),
            base_path('../ui/src'),
        ];

        $files = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['php', 'ts', 'tsx'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        $this->assertNotEmpty($files, 'The shipped-code sweep found nothing to check, so it is not testing anything.');

        return $files;
    }

    private function relative(string $file): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
    }

    /**
     * Comments are stripped because three of them quote the removed line on purpose, to
     * explain why it went. A guard that failed on its own documentation would get deleted
     * rather than fixed, which is how these come back.
     *
     * Crude on purpose: it cannot tell a `//` inside a string from a real comment, and
     * truncating such a line can only hide a number rather than invent one.
     */
    private function withoutComments(string $file): string
    {
        $source = (string) file_get_contents($file);

        $source = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;

        return preg_replace('#//.*$#m', '', $source) ?? $source;
    }
}
