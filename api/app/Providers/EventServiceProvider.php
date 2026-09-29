<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use App\Observers\AuthObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [AuthObserver::class . '@handleLogin'],
        Logout::class => [AuthObserver::class . '@handleLogout'],
        Failed::class => [AuthObserver::class . '@handleFailed'],
    ];
}
