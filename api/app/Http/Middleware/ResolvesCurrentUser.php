<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CurrentUserResolver;
use Illuminate\Http\Request;

trait ResolvesCurrentUser
{
    /**
     * Resolve the caller without depending on where auth:sanctum sits in the pipeline.
     */
    protected function currentUser(Request $request): ?User
    {
        return CurrentUserResolver::resolve($request);
    }
}
