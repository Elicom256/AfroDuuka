<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrentUserResolver
{
    /**
     * Resolve the current caller without depending on where auth:sanctum sits in the pipeline.
     *
     * The same request can pass through route-group middleware before the per-route
     * guard resolves the user, so checking only $request->user() is not enough. This
     * method falls back to the Sanctum guard and rebinds it to the active request when
     * the guard exposes the RequestGuard API.
     */
    public static function resolve(Request $request): ?User
    {
        $resolved = $request->user();

        if ($resolved instanceof User) {
            return $resolved;
        }

        $guard = Auth::guard('sanctum');

        if ($guard instanceof RequestGuard) {
            $guard->setRequest($request);
        }

        $user = $guard->user();

        return $user instanceof User ? $user : null;
    }
}
