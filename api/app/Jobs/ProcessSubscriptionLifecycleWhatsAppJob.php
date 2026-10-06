<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsApp\WhatsAppNotificationService;
use App\Support\Tenant\BusinessContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSubscriptionLifecycleWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $service = new WhatsAppNotificationService;

        // Deliberately read before any context is set. Every row on the install is the
        // subject of this sweep — it is looking for any business whose subscription has
        // lapsed — so this query must not itself be scoped to one tenant.
        $subscriptions = Subscription::withoutGlobalScopes()
            ->with(['business', 'plan'])
            ->where(function ($query) {
                $query->where('status', '!=', 'active')
                    ->orWhereNull('status');
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            $business = $subscription->business;

            if (! $business || empty($business->phone)) {
                continue;
            }

            // Inside the owning business, one subscription at a time. The query above has
            // no business_id predicate, so without this the three handlers below would run
            // with nothing to scope by — and each of them reads WhatsAppMessageLog and
            // Subscription, both tenant tables whose dedupe keys are per business.
            app(BusinessContext::class)->run($business->id, function () use ($subscription, $business, $service): void {
                $this->handleExpiryAlert($subscription, $business, $service);
                $this->handleExpiryReminder($subscription, $business, $service);
                $this->handleFreeTrialExpiryAlert($subscription, $business, $service);
            });
        }

        Log::info('Subscription lifecycle WhatsApp job completed');
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Subscription lifecycle WhatsApp job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }

    private function handleExpiryAlert(Subscription $subscription, $business, WhatsAppNotificationService $service): void
    {
        if (! $subscription->ends_at || ! $subscription->ends_at->isPast()) {
            return;
        }

        $dedupeKey = 'notification:subscription:expired:business-'.$business->id.':plan-'.($subscription->plan?->id ?? 'none');

        $existingLog = WhatsAppMessageLog::where('business_id', $business->id)
            ->where('dedupe_key', $dedupeKey)
            ->where('status', 'sent')
            ->first();

        if ($existingLog) {
            Log::info('Skipping duplicate subscription expiry notification', [
                'business_id' => $business->id,
                'dedupe_key' => $dedupeKey,
            ]);

            return;
        }

        $service->queueSubscriptionExpiryAlert([
            'business_id' => $business->id,
            'branch_id' => null,
            'business_name' => $business->name,
            'plan_name' => $subscription->plan?->name ?? 'Your plan',
            'expiry_date' => $subscription->ends_at->toDateString(),
            'recipient_phone' => $business->phone,
        ]);
    }

    /**
     * Overdue notice, sent on a 2-day cadence after expiry.
     *
     * This is deliberately *not* the catalogue's `subscription.expiring`, and the two
     * cannot be merged. That one is a pre-expiry reminder; this one only ever runs
     * after `ends_at` has passed, because an expired subscription is the only state
     * worth chasing. Treating them as the same notification under an older name is
     * what would let this job suppress a reminder the customer has not had yet.
     *
     * The bucket is keyed on days *overdue*, and the payload says so. It used to pass
     * this count into a `days_remaining` field, so a customer three days past expiry was
     * carrying "3 days remaining" — the one number in the message that was wrong, in the
     * direction that made the message read as reassuring.
     */
    private function handleExpiryReminder(Subscription $subscription, $business, WhatsAppNotificationService $service): void
    {
        if (! $subscription->ends_at || ! $subscription->ends_at->isPast()) {
            return;
        }

        $daysOverdue = (int) now()->diffInDays($subscription->ends_at);

        if ($daysOverdue < 2 || $daysOverdue % 2 !== 0) {
            return;
        }

        $dedupeKey = 'notification:subscription:overdue:business-'.$business->id.':days-'.$daysOverdue;

        $existingLog = WhatsAppMessageLog::where('business_id', $business->id)
            ->where('dedupe_key', $dedupeKey)
            ->where('status', 'sent')
            ->first();

        if ($existingLog) {
            Log::info('Skipping duplicate subscription overdue notice', [
                'business_id' => $business->id,
                'dedupe_key' => $dedupeKey,
            ]);

            return;
        }

        $service->queueSubscriptionReminderAlert([
            'business_id' => $business->id,
            'branch_id' => null,
            'business_name' => $business->name,
            'plan_name' => $subscription->plan?->name ?? 'Your plan',
            'days_overdue' => $daysOverdue,
            'recipient_phone' => $business->phone,
        ]);
    }

    private function handleFreeTrialExpiryAlert(Subscription $subscription, $business, WhatsAppNotificationService $service): void
    {
        if (! $subscription->trial_ends_at || ! $subscription->trial_ends_at->isPast()) {
            return;
        }

        $hasActiveSubscription = Subscription::where('business_id', $business->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->exists();

        $dedupeKey = 'notification:subscription:free_trial_expired:business-'.$business->id;

        $existingLog = WhatsAppMessageLog::where('business_id', $business->id)
            ->where('dedupe_key', $dedupeKey)
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subDays(7))
            ->first();

        if ($hasActiveSubscription || $existingLog) {
            return;
        }

        $service->queueFreeTrialExpiryAlert([
            'business_id' => $business->id,
            'branch_id' => null,
            'business_name' => $business->name,
            'trial_end_date' => $subscription->trial_ends_at->toDateString(),
            'recipient_phone' => $business->phone,
        ]);
    }
}
