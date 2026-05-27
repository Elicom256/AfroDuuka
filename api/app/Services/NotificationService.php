<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Create a notification for a specific user
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $notifiableType = null,
        ?int $notifiableId = null
    ): Notification {
        return Notification::create([
            'user_id'         => $user->id,
            'business_id'         => $user->business_id,
            'type'            => $type,
            'title'           => $title,
            'message'         => $message,
            'data'            => $data,
            'notifiable_type' => $notifiableType,
            'notifiable_id'   => $notifiableId,
            'is_read'         => false,
        ]);
    }

    /**
     * Create notification for currently authenticated user
     */
    public function createForCurrentUser(
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $notifiableType = null,
        ?int $notifiableId = null
    ): ?Notification {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return $this->create($user, $type, $title, $message, $data, $notifiableType, $notifiableId);
    }

    /**
     * Notify multiple users (e.g. all admins)
     */
    public function notifyMultiple(
        array $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $notifiableType = null,
        ?int $notifiableId = null
    ): void {
        foreach ($users as $user) {
            $this->create($user, $type, $title, $message, $data, $notifiableType, $notifiableId);
        }
    }

    /**
     * Common Business Notifications
     */

    public function lowStockAlert(User $user, string $productName, int $currentStock, int $reorderLevel): Notification
    {
        return $this->create(
            $user,
            'low_stock',
            'Low Stock Alert',
            "Product '{$productName}' is low on stock. Only {$currentStock} left (Reorder Level: {$reorderLevel}).",
            [
                'product_name' => $productName,
                'current_stock' => $currentStock,
                'reorder_level' => $reorderLevel,
            ]
        );
    }

    public function newSaleRecorded(User $user, string $saleAmount, ?string $customerName = null): Notification
    {
        $customerText = $customerName ? " to {$customerName}" : '';

        return $this->create(
            $user,
            'new_sale',
            'New Sale Recorded',
            "A new sale of UGX {$saleAmount}{$customerText} was made.",
            ['amount' => $saleAmount, 'customer' => $customerName]
        );
    }

    public function newPurchaseRecorded(User $user, string $supplierName, string $amount): Notification
    {
        return $this->create(
            $user,
            'new_purchase',
            'New Purchase Recorded',
            "New purchase of UGX {$amount} from {$supplierName}.",
            ['supplier' => $supplierName, 'amount' => $amount]
        );
    }

    public function paymentReceived(User $user, string $amount, string $method): Notification
    {
        return $this->create(
            $user,
            'payment_received',
            'Payment Received',
            "UGX {$amount} received via {$method}.",
            ['amount' => $amount, 'method' => $method]
        );
    }
}