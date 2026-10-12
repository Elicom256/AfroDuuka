<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Raised when an account has burnt through its failed-login allowance.
 *
 * Rendered as 429 with a Retry-After header rather than 401. The distinction is the
 * point: a 401 reads as "your password is wrong", which invites the caller to try a
 * different one immediately, and the retry is what turns a lockout into a credential
 * guessing schedule. 429 plus Retry-After tells a well-behaved client when to come
 * back, and says plainly that the door is closed for now.
 *
 * The message deliberately says nothing about whether the account exists.
 */
class TooManyLoginAttemptsException extends HttpException
{
    public function __construct(public readonly int $secondsRemaining)
    {
        parent::__construct(429, 'Too many failed login attempts. Please try again later.');
    }

    public function getSecondsRemaining(): int
    {
        return max(1, $this->secondsRemaining);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'retry_after' => $this->getSecondsRemaining(),
        ], 429, [
            'Retry-After' => $this->getSecondsRemaining(),
        ]);
    }
}
