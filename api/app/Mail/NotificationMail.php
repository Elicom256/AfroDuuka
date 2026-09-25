<?php

namespace App\Mail;

use App\Mail\Concerns\HasUnsubscribeHeaders;
use App\Models\NotificationDelivery;
use App\Services\Ses\NotificationMessageId;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The one Mailable behind every notification email.
 *
 * Deliberately not one class per notification. The catalogue has 14 types and grows
 * when the product does, so a class per type would mean a class, a view and a factory
 * mapping for every new notification, all of them structurally identical apart from a
 * subject line and a few paragraphs. A notification the product adds would then need
 * three files changed in three places before it could send its first email, and the
 * probability that all three are updated is the probability that the notification
 * silently never sends.
 *
 * A single parameterised mailable makes an unknown type fail loudly instead: see
 * EmailMailableFactory, which throws for a type with no subject rather than sending
 * something with a blank subject line.
 *
 * The body is a plain view driven by the delivery's own values. Nothing is rendered
 * from a template the owner can edit — email copy is ours, and per-notification
 * wording belongs in the view, not in a database row a tenant can change.
 */
class NotificationMail extends Mailable
{
    use HasUnsubscribeHeaders;
    use Queueable;
    use SerializesModels;

    protected bool $suppressUnsubscribe = false;

    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public readonly NotificationDelivery $delivery,
        public readonly string $type,
        public readonly array $values,
    ) {}

    public function build(): static
    {
        $mail = $this->subject($this->subjectLine())
            // Taken from the delivery row, not from the recipient model: the address on
            // the delivery is the snapshot that was resolved and permission-checked, and
            // re-reading the recipient here would send to whatever the address is now
            // rather than the one that was approved for this notification.
            ->to($this->delivery->recipient_address)
            ->from(
                config('notifications.email.from_address'),
                config('notifications.email.from_name')
            )
            ->when(
                config('notifications.email.reply_to'),
                fn ($m) => $m->replyTo(config('notifications.email.reply_to'))
            )
            ->withSymfonyMessage(function ($message) {
                // The correlation key for bounces and complaints. Without it SES has
                // nothing to report against but the recipient address, and a bounce
                // could not be attributed to the delivery that caused it.
                $message->getHeaders()->addIdHeader(
                    'Message-ID',
                    NotificationMessageId::forDelivery($this->delivery)
                );
            })
            ->view('mail.notification', [
                'type' => $this->type,
                'headline' => $this->headline(),
                'body' => $this->body(),
                'details' => $this->details(),
                'actionUrl' => $this->actionUrl(),
                'actionLabel' => $this->actionLabel(),
            ]);

        return $this->applyUnsubscribeHeaders($mail);
    }

    private function applyUnsubscribeHeaders($mail): static
    {
        if ($this->suppressUnsubscribe || $this->delivery->is_mandatory) {
            return $mail;
        }

        $url = $this->unsubscribeUrl($this->delivery);

        return $mail->withSymfonyMessage(function ($message) use ($url) {
            $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$url.'>');
            $message->getHeaders()->addTextHeader(
                'List-Unsubscribe-Post',
                'List-Unsubscribe=One-Click'
            );
        });
    }

    private function subjectLine(): string
    {
        $values = $this->values;
        $name = $values['business_name'] ?? config('app.name');

        return match ($this->type) {
            'registration.welcome' => "Welcome to DuukaFlow, {$name}",
            'subscription.activated' => "Your {$values['plan_name']} plan is active",
            'subscription.plan_changed' => 'Your plan has changed',
            'subscription.renewed' => 'Your subscription has been renewed',
            'payment.failed' => 'Action needed: your payment failed',
            'subscription.expired' => 'Your subscription has expired',
            'subscription.expiring' => 'Your subscription expires soon',
            'trial.ended' => 'Your free trial has ended',
            'report.monthly' => "Your {$values['period']} report is ready",
            'quotation.sent' => "Quotation {$values['quotation_number']}",
            default => 'A DuukaFlow update',
        };
    }

    private function headline(): string
    {
        return match ($this->type) {
            'registration.welcome' => 'Your business is live',
            'subscription.activated' => 'Subscription active',
            'subscription.plan_changed' => 'Plan updated',
            'subscription.renewed' => 'Subscription renewed',
            'payment.failed' => 'Payment failed',
            'subscription.expired' => 'Subscription expired',
            'subscription.expiring' => 'Subscription expiring',
            'trial.ended' => 'Free trial ended',
            'report.monthly' => 'Monthly report ready',
            'quotation.sent' => 'Quotation sent',
            default => 'Update',
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function details(): array
    {
        $v = $this->values;

        return match ($this->type) {
            'registration.welcome' => array_filter([
                ['label' => 'Business', 'value' => (string) ($v['business_name'] ?? '')],
                ['label' => 'Trial ends', 'value' => (string) ($v['trial_ends_at'] ?? '')],
            ]),
            'subscription.activated', 'subscription.renewed' => array_filter([
                ['label' => 'Plan', 'value' => (string) ($v['plan_name'] ?? '')],
                ['label' => 'Valid until', 'value' => (string) ($v['ends_at'] ?? '')],
            ]),
            'subscription.plan_changed' => array_filter([
                ['label' => 'New plan', 'value' => (string) ($v['plan_name'] ?? '')],
                ['label' => 'Effective', 'value' => (string) ($v['effective_at'] ?? '')],
            ]),
            'payment.failed' => array_filter([
                ['label' => 'Amount', 'value' => (string) ($v['amount'] ?? '')],
                ['label' => 'Reason', 'value' => (string) ($v['reason'] ?? '')],
            ]),
            'subscription.expired', 'subscription.expiring' => array_filter([
                ['label' => 'Expired on', 'value' => (string) ($v['ends_at'] ?? '')],
                ['label' => 'Days remaining', 'value' => isset($v['days_remaining']) ? (string) $v['days_remaining'] : ''],
            ]),
            'trial.ended' => array_filter([
                ['label' => 'Trial ended', 'value' => (string) ($v['trial_ends_at'] ?? '')],
            ]),
            'report.monthly' => array_filter([
                ['label' => 'Period', 'value' => (string) ($v['period'] ?? '')],
            ]),
            'quotation.sent' => array_filter([
                ['label' => 'Quotation', 'value' => (string) ($v['quotation_number'] ?? '')],
                ['label' => 'Total', 'value' => (string) ($v['total'] ?? '')],
            ]),
            default => [],
        };
    }

    private function body(): string
    {
        return match ($this->type) {
            'registration.welcome' => 'Your business is set up and ready to use. Your free trial is running.',
            'subscription.activated' => 'Your subscription is now active. The new limits apply immediately.',
            'subscription.plan_changed' => 'Your subscription plan has changed. The new limits apply immediately.',
            'subscription.renewed' => 'Your subscription has been renewed. Thank you.',
            'payment.failed' => 'We could not take the payment for your subscription. Update your payment details to keep your account active.',
            'subscription.expired' => 'Your subscription has expired. Reactivate to pick up where you left off.',
            'subscription.expiring' => 'Your subscription is about to expire. Renew to avoid losing access.',
            'trial.ended' => 'Your free trial has ended. Subscribe to keep selling.',
            'report.monthly' => 'Your monthly performance report is ready to view in DuukaFlow.',
            'quotation.sent' => 'A quotation has been sent to your customer.',
            default => '',
        };
    }

    private function actionUrl(): ?string
    {
        $v = $this->values;

        foreach (['renew_url', 'subscribe_url', 'retry_url', 'report_url', 'url'] as $key) {
            if (filled($v[$key] ?? null)) {
                return (string) $v[$key];
            }
        }

        return null;
    }

    private function actionLabel(): ?string
    {
        return match ($this->type) {
            'payment.failed' => 'Retry payment',
            'subscription.expired' => 'Reactivate',
            'subscription.expiring' => 'Renew now',
            'trial.ended' => 'Subscribe',
            'report.monthly' => 'View report',
            default => $this->actionUrl() !== null ? 'Open DuukaFlow' : null,
        };
    }
}
