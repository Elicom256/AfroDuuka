<?php

use App\Jobs\CheckNotificationsJob;
use App\Jobs\GenerateMonthlyBusinessReportJob;
use App\Jobs\ProcessSubscriptionLifecycleWhatsAppJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============== Job Schedule ===================
Schedule::job(new CheckNotificationsJob())->everySixHours()->withoutOverlapping();

// Expiry reminders are bucketed in 2-day steps, so a 6h cadence buys nothing.
Schedule::job(new ProcessSubscriptionLifecycleWhatsAppJob())->dailyAt('07:00')->withoutOverlapping();

// Previous completed month. Was previously unscheduled (held only by the dead Console/Kernel).
Schedule::job(new GenerateMonthlyBusinessReportJob())->monthlyOn(1, '08:00')->withoutOverlapping();

// ============== Schedule commands ====================
Schedule::command("inspire")->hourly();

// Meta approval can be granted, revoked or limited at any time, and a template we
// believe is APPROVED is the gate for every WhatsApp send. Daily is a compromise:
// the only real cost of a stale status is sending against a revoked template, which
// Meta rejects, and the retries are already capped.
Schedule::command('duukaflow:whatsapp:sync-templates')
    ->dailyAt('06:30')
    ->withoutOverlapping();