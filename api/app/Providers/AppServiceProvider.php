<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\PriceHistoryObserver;
use App\Support\Activity\SafeActivityLogger;
use App\Support\Tenant\BusinessContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\ActivityLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A single instance for the whole request or job, so a context set deep inside
        // a job is visible to every model query that follows.
        $this->app->singleton(BusinessContext::class, fn () => new BusinessContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Resolved lazily by the LogsActivity trait on every save, so rebinding here
        // (after Spatie's own provider registered) is what makes every model's audit
        // write failure-tolerant. See SafeActivityLogger.
        $this->app->bind(ActivityLogger::class, SafeActivityLogger::class);

        Product::observe(PriceHistoryObserver::class);

        $this->clearBusinessContextBetweenJobs();

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Verification resends. Keyed by the authenticated user, not the IP, because
        // this is one account mailing one address — an office behind a single NAT
        // would otherwise share a budget and lock each other out. The per-business
        // cooldown in BusinessVerificationMailService is the second, slower floor.
        RateLimiter::for('verification', function (Request $request) {
            return Limit::perHour(6)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * A queue worker is a long-lived process handling many businesses in sequence. If a
     * job sets a tenant context and then throws before its finally block runs, the next
     * job would inherit it and write to the wrong business. Clearing on both the
     * processed and the failed path makes that leak impossible.
     */
    protected function clearBusinessContextBetweenJobs(): void
    {
        foreach ([JobProcessing::class, JobProcessed::class, JobExceptionOccurred::class] as $event) {
            Event::listen($event, function () {
                $this->app->make(BusinessContext::class)->clear();
            });
        }
    }
}
