<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\ProcessWhatsAppNotificationJob;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The hardcoded recipient number, removed.
 *
 * `+256731794401` appeared as a fallback recipient in the monthly report job, seven
 * listeners and the subscription controller, and as a default sender identity in the
 * config. Every one of them meant the same thing: a business with no phone on file had
 * its notifications — subscription expiry, payment failure, sales, purchases, and a
 * monthly report containing its revenue and profit — delivered to one developer's
 * personal handset.
 *
 * What made this survivable for so long is that nothing about it looked wrong. The
 * message was composed and sent, the log recorded a recipient, the row said "sent". The
 * only symptom was a stranger's phone quietly filling up with other people's finances.
 *
 * The new pipeline was never affected: `RecipientResolver` returns null instead of
 * guessing and the channel rejects a delivery with no address. This was a legacy-layer
 * habit, which is a fair argument for retiring that layer rather than patching it
 * forever.
 */
class LegacyHardcodedRecipientTest extends TestCase
{
    use RefreshDatabase;

    private const HARDCODED = '+256731794401';

    public function test_a_business_with_no_phone_gets_no_monthly_report(): void
    {
        // The monthly report job cannot execute at all, so it is asserted at the source
        // level rather than by running it. `purchases` is scoped by business_branch_id
        // and has no business_id column, so `Purchase::where('business_id', ...)` raises
        // an SQL error on the first business and the job has never completed a run.
        //
        // Which means the hardcoded recipient in it was latent rather than live, and that
        // retiring it costs no coverage — there has never been any. See the finding in
        // undone.md before assuming the monthly report is being sent today.
        $source = file_get_contents(app_path('Jobs/GenerateMonthlyBusinessReportJob.php'));
        $code = preg_replace('#//.*$#m', '', $source) ?? '';

        $this->assertStringNotContainsString(
            '256731794401',
            $code,
            'The monthly report job can still fall back to a hardcoded recipient.'
        );
    }

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
     * The listeners resolved their recipient inline, seven times over, so the pattern is
     * asserted against the source rather than trusted. A new listener copying the old
     * line would reintroduce the leak with no behavioural test to catch it, because the
     * guard downstream would simply skip the send and the omission would look like
     * correct behaviour.
     */
    public function test_no_listener_falls_back_to_a_hardcoded_number(): void
    {
        $offenders = [];

        foreach (glob(app_path('Listeners/WhatsApp/*.php')) as $file) {
            $source = file_get_contents($file);

            // Ignore the comment that documents the removal.
            $code = preg_replace('#//.*$#m', '', $source) ?? '';

            if (str_contains($code, '256731794401') || preg_match("/recipient_phone'?\s*=>[^,\n]*\?\?/i", $code)) {
                $offenders[] = basename($file);
            }
        }

        $this->assertSame([], $offenders, 'A listener can still substitute its own recipient.');
    }
}
