<?php

namespace App\Console;

use App\Jobs\GenerateMonthlyBusinessReportJob;
use App\Jobs\ProcessSubscriptionLifecycleWhatsAppJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Art commands provided by your application.
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(ProcessSubscriptionLifecycleWhatsAppJob::class)
            ->everyTwoMinutes()
            ->onSuccess(function () {
                //
            })
            ->onFailure(function (\Illuminate\Queue\Jobs\Job $job, $exception) {
                //
            });

        $schedule->job(GenerateMonthlyBusinessReportJob::class)
            ->monthlyOn(1, '01:00');

        $schedule->job(ProcessSubscriptionLifecycleWhatsAppJob::class)
            ->everySixHours()
            ->toggleOutput();
    }
}