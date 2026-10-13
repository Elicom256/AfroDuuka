<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Raised by App\Http\Middleware\RequireFeature when a request reaches a route
 * whose feature is switched off for the business.
 *
 * It is an HttpException(403) rather than a 404 on purpose: the route and the
 * page both exist, and 404 would tell a user the thing is not there when the
 * real answer is "not for you right now". ApiExceptionRenderer maps it to the
 * `feature_disabled` code so the frontend can say so explicitly.
 */
class FeatureDisabledException extends HttpException
{
    public function __construct(
        public readonly string $feature,
        string $message,
    ) {
        parent::__construct(403, $message);
    }
}
