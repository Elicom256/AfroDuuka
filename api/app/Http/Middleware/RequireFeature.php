<?php

namespace App\Http\Middleware;

use App\Exceptions\FeatureDisabledException;
use App\Support\Features\FeatureFlags;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a route behind a feature flag.
 *
 * Usage: `->middleware('feature:reports')`.
 *
 * The alternative — checking a flag inside each controller — left the route
 * documented and reachable by direct navigation, and duplicated the same
 * condition across every entry point of a feature. One middleware, resolved by
 * App\Support\Features\FeatureFlags, means the answer is the same no matter
 * which route or which role asked.
 */
class RequireFeature
{
    public function __construct(private readonly FeatureFlags $flags) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! $this->flags->enabled($feature)) {
            throw new FeatureDisabledException($feature, $this->flags->message($feature));
        }

        return $next($request);
    }
}
