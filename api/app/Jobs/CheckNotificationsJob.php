<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user;
    }

    public function handle(NotificationService $notificationService): void
    {
        Log::info('CheckNotificationsJob started');

        $this->checkLowStock($notificationService);
        $this->checkOverduePayments($notificationService);

        Log::info('CheckNotificationsJob completed');
    }

    /**
     * Check for low stock products
     */
    private function checkLowStock(NotificationService $service): void
    {
        $lowStockProducts = Product::with('productCategory')
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->get();

        foreach ($lowStockProducts as $item) {
            $alreadyNotified = Notification::where('type', 'low_stock')
                ->where('notifiable_type', Product::class)
                ->where('notifiable_id', $item->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if (!$alreadyNotified) {
                $service->lowStockAlert(
                    $this->user ?? $this->getAdminUser(),
                    $item->name,
                    $item->quantity,
                    $item->reorder_level
                );
                Log::info("Low stock alert sent for: {$item->name}");
            }
        }
    }

    /**
     * Check for overdue payments (example)
     */
    private function checkOverduePayments(NotificationService $service): void
    {
        $overdueCustomers = Customer::whereHas('sales', function ($q) {
            $q->where('paymentStatus', 'pending')
              ->where('created_at', '<=', now()->subDays(30));
        })->get();

        foreach ($overdueCustomers as $customer) {
            $service->create(
                $this->user ?? $this->getAdminUser(),
                'overdue_payment',
                'Overdue Payment Alert',
                "Customer {$customer->fullname} has overdue payments.",
                ['customer_id' => $customer->id]
            );
        }
    }

    /**
     * Fallback: Get first admin/super admin
     */
    private function getAdminUser()
    {
        return User::where('role', 'admin')
            ->whereNotNull('business_id')
            ->first() ?? User::where('role', 'admin')->first() ?? User::first();
    }
}