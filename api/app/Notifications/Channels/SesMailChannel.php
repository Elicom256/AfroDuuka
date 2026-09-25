<?php

namespace App\Notifications\Channels;

use App\Contracts\Notifications\ChannelResult;
use App\Contracts\Notifications\NotificationChannel;
use App\Mail\NotificationMail;
use App\Models\NotificationDelivery;
use App\Services\Ses\NotificationMessageId;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

/**
 * Email transport, over Amazon SES.
 *
 * Called from inside SendNotificationJob, which is already queued. The mail is
 * therefore sent synchronously here rather than queued again: double-queueing would put
 * a second, invisible retry in the system that has no dedupe key, so a send that failed
 * ambiguously would be retried by the queue *and* by our own attempt budget, and the
 * recipient could get the email twice.
 *
 * That is also why the ambiguous result matters more here than for WhatsApp. A
 * transport exception is ambiguous, not a rejection: SES may have accepted the message
 * before the connection dropped. Reporting it as failed invites a resend of mail the
 * customer has already received. Reporting it as ambiguous parks the delivery in
 * `sending` for the bounce/delivery webhook to resolve, which is the only honest
 * answer available at this point.
 */
class SesMailChannel implements NotificationChannel
{
    public function name(): string
    {
        return 'email';
    }

    public function isAvailable(NotificationDelivery $delivery): bool
    {
        // No address means nothing to send to, which is a suppression rather than an
        // availability question.
        return filled($delivery->recipient_address) && $this->mailer() !== null;
    }

    public function unavailableReason(NotificationDelivery $delivery): ?string
    {
        if (blank($delivery->recipient_address)) {
            return NotificationDelivery::REASON_NO_RECIPIENT;
        }

        return $this->mailer() === null ? 'no_mailer' : null;
    }

    public function send(NotificationDelivery $delivery, array $parameters): ChannelResult
    {
        $address = (string) $delivery->recipient_address;

        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return ChannelResult::rejected('bad_address', 'Recipient is not a valid email address.');
        }

        $factory = $this->mailer();

        if ($factory === null) {
            return ChannelResult::rejected('no_mailer', 'No mail transport is configured.');
        }

        try {
            // Named through the Factory contract, not the Mailer contract: only the
            // factory knows about MAIL_MAILER, and Mailer::mailer() does not exist.
            $sent = $factory->mailer($this->mailerName())->send(
                new NotificationMail($delivery, $delivery->type, $delivery->values()),
            );
        } catch (TransportException $e) {
            // Ambiguous, not failed. See the class docblock.
            Log::warning('SES transport error, delivery left ambiguous', [
                'delivery_id' => $delivery->id,
                'type' => $delivery->type,
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::ambiguous($e->getMessage());
        } catch (Throwable $e) {
            // Anything else is ours, not the network's: a missing view, a bad mailable.
            // Retrying cannot help and would burn the attempt budget.
            Log::error('SES mail build failed', [
                'delivery_id' => $delivery->id,
                'type' => $delivery->type,
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::rejected('build_failed', $e->getMessage());
        }

        if ($sent === false) {
            return ChannelResult::rejected('not_accepted', 'The mail transport declined the message.');
        }

        // The Message-ID, not a synthetic value: it is what SES will echo on the
        // bounce, and the delivery row is what a later suppression is applied to.
        return ChannelResult::accepted(NotificationMessageId::forDelivery($delivery));
    }

    /**
     * The mail factory, or null when the mail stack is not bound.
     *
     * Resolved rather than injected so this class stays constructible in tests and in
     * the console, where the container may not have bound the mail stack yet.
     */
    private function mailer(): ?MailFactory
    {
        try {
            return app(MailFactory::class);
        } catch (Throwable) {
            return null;
        }
    }

    private function mailerName(): string
    {
        $configured = config('mail.default');

        // `log` and `array` are legitimate: they are how the pipeline is exercised
        // locally and in tests without SES credentials. Treating only the log driver as
        // "no transport" would make local development impossible.
        return is_string($configured) && $configured !== '' ? $configured : 'log';
    }
}
