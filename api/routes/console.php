<?php

use App\Jobs\CheckNotificationsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============== Job Schedule ===================
Schedule::job(new CheckNotificationsJob())->everySixHours()->withoutOverlapping();
// ============== Schedule commands ====================
Schedule::command("inspire")->hourly();
Schedule::command("queue:work")->everyMinute();