<?php

namespace App\Listeners\WhatsApp;

use App\Events\WhatsAppNotificationEvents;
use App\Services\WhatsApp\WhatsAppNotificationService;

class BusinessRegisteredListener
{
    public function handle(WhatsAppNotificationEvents\BusinessRegistered $event): void
    {
        (new WhatsAppNotificationService)->queueBusinessNotification([
            'business_id' => $event->business->id,
            'type' => 'registration',
            'template_key' => 'registration.welcome',
            'recipient_phone' => $event->business->phone,
            'template_data' => [
                'business_name' => $event->business->name,
                'phone' => $event->business->phone,
            ],
        ]);
    }
}
