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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $user = Auth::user();
        $lowStockProducts = Product::with('productCategory')
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<='. 'reorder_level')          // threshold
            ->get();

        foreach ($lowStockProducts as $item) {
            // Avoid spamming the same user with same alert
            $alreadyNotified = Notification::where('type', 'low_stock')
                ->where('notifiable_type', Product::class)
                ->where('notifiable_id', $item->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if (!$alreadyNotified && $item) {
                $service->lowStockAlert(
                    $user ?? $this->getAdminUser(),   // fallback to main admin
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
    // ================= to point this to credits
    private function checkOverduePayments(NotificationService $service): void
    {
        $user = Auth::user();
        // Example: Customers with pending payments older than 30 days
        $overdueCustomers = Customer::whereHas('sales', function ($q) {
            $q->where('paymentStatus', 'pending')
              ->where('created_at', '<=', now()->subDays(30));
        })->get();

        foreach ($overdueCustomers as $customer) {
            $service->create(
                $user ?? $this->getAdminUser(),
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
        return User::where("role", "admin")->where("business_id", Auth::user()->business_id)->first();
    }
}