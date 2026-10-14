<?php

namespace App\Listeners;

use App\Events\WhatsAppNotificationEvents\BusinessRegistered;
use App\Services\Mail\BusinessVerificationMailService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/**
 * Sends the verification email for a newly registered business.
 *
 * Queued after commit on purpose. This listener used to run inline inside
 * BusinessService::create()'s transaction, so the Resend call happened before the
 * business row was committed — and any transport failure (the account still in
 * Resend's testing mode, a rate limit, a bad API key) rolled the whole onboarding
 * back. The owner saw the sanitised "Something went wrong on our side" 500 and lost
 * the tenant over an email. A mail problem must never abort tenant creation: the
 * welcome mail is recoverable, the signup is not.
 */
class SendBusinessVerificationEmailListener implements ShouldQueueAfterCommit
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