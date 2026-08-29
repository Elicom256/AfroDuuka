<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionLifecycleWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $service = new WhatsAppNotificationService();

        $subscriptions = Subscription::with(['business', 'plan'])
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

            $this->handleExpiryAlert($subscription, $business, $service);
            $this->handleExpiryReminder($subscription, $business, $service);
            $this->handleFreeTrialExpiryAlert($subscription, $business, $service);
        }

        Log::info('Subscription lifecycle WhatsApp job completed');
    }

    private function handleExpiryAlert(Subscription $subscription, $business, WhatsAppNotificationService $service): void
    {
        if (! $subscription->ends_at || ! $subscription->ends_at->isPast()) {
            return;
        }

        if ($this->hasRecentAlert($business->id, 'subscription.expired')) {
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

    private function handleExpiryReminder(Subscription $subscription, $business, WhatsAppNotificationService $service): void
    {
        if (! $subscription->ends_at || ! $subscription->ends_at->isPast()) {
            return;
        }

        $daysSinceExpiry = (int) now()->diffInDays($subscription->ends_at);

        if ($daysSinceExpiry < 2 || $daysSinceExpiry % 2 !== 0) {
            return;
        }

        if ($this->hasRecentAlert($business->id, 'subscription.expiry_reminder')) {
            return;
        }

        $service->queueSubscriptionReminderAlert([
            'business_id' => $business->id,
            'branch_id' => null,
            'business_name' => $business->name,
            'plan_name' => $subscription->plan?->name ?? 'Your plan',
            'days_remaining' => $daysSinceExpiry,
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

        if ($hasActiveSubscription || $this->hasRecentAlert($business->id, 'subscription.free_trial_expired')) {
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

    private function hasRecentAlert(int $businessId, string $alertType): bool
    {
        return WhatsAppMessageLog::where('business_id', $businessId)
            ->where('status', 'sent')
            ->where('message_body', 'like', '%' . $alertType . '%')
            ->where('sent_at', '>=', now()->subDays(2))
            ->exists();
    }
}
