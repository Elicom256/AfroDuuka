<?php

namespace App\Listeners;

use App\Events\WhatsAppNotificationEvents\BusinessRegistered;
use App\Services\Mail\BusinessVerificationMailService;

class SendBusinessVerificationEmailListener
{
    public function __construct(
        private readonly BusinessVerificationMailService $verificationService,
    ) {}

    public function handle(BusinessRegistered $event): void
    {
        $business = $event->business;

        if (! $business->email) {
            return; // No email to send to
        }

        $this->verificationService->send($business);
    }
}