<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Normalise an analytics `period` query parameter against an allowed set.
     *
     * Analytics endpoints all accept a period and all need the same fallback when the
     * client sends something unknown (or nothing at all). Left inline, each controller
     * reinvented its own default and an unrecognised value could silently reach the
     * query builder; centralising the check keeps the default consistent and makes the
     * rejected values testable without an HTTP round trip.
     *
     * @param  string|null       $period  Requested period, if any.
     * @param  array<int,string> $allowed Permitted values, most-preferred first.
     * @return string The requested value when allowed, otherwise the first allowed one.
     */
    public static function resolveAnalyticsPeriod(?string $period, array $allowed): string
    {
        if ($period !== null && in_array($period, $allowed, true)) {
            return $period;
        }

        return $allowed[0] ?? '';
    }
}
