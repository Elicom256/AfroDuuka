<?php

namespace App\Providers;

use App\Observers\AuthObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [AuthObserver::class.'@handleLogin'],
        Logout::class => [AuthObserver::class.'@handleLogout'],
        Failed::class => [AuthObserver::class.'@handleFailed'],
    ];
}
