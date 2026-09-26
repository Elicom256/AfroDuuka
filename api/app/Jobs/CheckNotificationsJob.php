<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300];
    }

    private ?User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user;
    }

    public function handle(NotificationService $notificationService): void
    {
        Log::info('CheckNotificationsJob started');

        $businesses = $this->usersToCheck();

        foreach ($businesses as $business) {
            $recipients = $this->recipientsFor($business);
            if ($recipients->isEmpty()) {
                continue;
            }

            $this->checkLowStock($notificationService, $business, $recipients);
            $this->checkOverduePayments($notificationService, $business, $recipients);
        }

        Log::info('CheckNotificationsJob completed');
    }

    public function failed(Throwable $exception): void
    {
        Log::error('CheckNotificationsJob failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * When run directly for a user, only that user's business is scanned.
     * When scheduled, scan the businesses that at least one admin belongs to.
     */
    private function businessesToCheck(): Collection
    {
        if ($this->user && $this->user->business_id) {
            return collect([Business::find($this->user->business_id)])->filter();
        }

        return Business::whereHas('users', function ($q) {
            $q->whereHas('role', fn ($r) => $r->where('name', 'admin'));
        })->get();
    }

    /**
     * Admins for the business (or the single user when run directly).
     */
    private function recipientsFor(Business $business): Collection
    {
        if ($this->user && $this->user->business_id === $business->id) {
            return collect([$this->user]);
        }

        return User::query()
            ->where('business_id', $business->id)
            ->where('status', 'active')
            ->whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->get();
    }

    /**
     * Check for low stock products
     */
    private function checkLowStock(NotificationService $service, Business $business, Collection $recipients): void
    {
        $branchIds = BusinessBranch::where('business_id', $business->id)->pluck('id');

        $lowStockProducts = Product::with('productCategory')
            ->whereIn('business_branch_id', $branchIds)
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->get();

        foreach ($lowStockProducts as $item) {
            foreach ($recipients as $recipient) {
                $alreadyNotified = Notification::where('user_id', $recipient->id)
                    ->where('business_id', $business->id)
                    ->where('type', 'low_stock')
                    ->where('notifiable_type', Product::class)
                    ->where('notifiable_id', $item->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->exists();

                if (!$alreadyNotified) {
                    $service->lowStockAlert(
                        $recipient,
                        $item->name,
                        $item->quantity,
                        $item->reorder_level,
                        $item->id
                    );
                    Log::info("Low stock alert sent for: {$item->name}");
                }
            }
        }
    }

    /**
     * Check for overdue payments (example)
     */
    private function checkOverduePayments(NotificationService $service, Business $business, Collection $recipients): void
    {
        $overdueCustomers = Customer::where('business_id', $business->id)
            ->whereHas('sales', function ($q) {
                $q->where('status', 'pending')
                  ->where('created_at', '<=', now()->subDays(30));
            })->get();

        foreach ($overdueCustomers as $customer) {
            foreach ($recipients as $recipient) {
                $alreadyNotified = Notification::where('user_id', $recipient->id)
                    ->where('business_id', $business->id)
                    ->where('type', 'overdue_payment')
                    ->where('notifiable_type', Customer::class)
                    ->where('notifiable_id', $customer->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->exists();

                if (!$alreadyNotified) {
                    $service->create(
                        $recipient,
                        'overdue_payment',
                        'Overdue Payment Alert',
                        'Customer ' . $customer->name() . ' has overdue payments.',
                        ['customer_id' => $customer->id],
                        Customer::class,
                        $customer->id
                    );
                }
            }
        }
    }
}