<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class AuthObserver
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        ActivityLog::create([
            'log_name' => 'Authentication',
            'event' => 'logged_in',
            'description' => 'User logged in',
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'causer_type' => get_class($user),
            'causer_id' => $user->id,
            'properties' => ['ip' => Request::ip(), 'user_agent' => Request::userAgent()],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if (! $user) {
            return;
        }

        ActivityLog::create([
            'log_name' => 'Authentication',
            'event' => 'logged_out',
            'description' => 'User logged out',
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'causer_type' => get_class($user),
            'causer_id' => $user->id,
            'properties' => ['ip' => Request::ip()],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        $user = $event->user;

        ActivityLog::create([
            'log_name' => 'Authentication',
            'event' => 'failed',
            'description' => 'Failed login attempt',
            'subject_type' => $user ? get_class($user) : null,
            'subject_id' => $user?->id,
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => [
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'credentials' => ['email' => $event->credentials['email'] ?? null],
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
