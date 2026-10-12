<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Throttles repeated failed logins against a single account.
 *
 * The route-level `throttle:auth` limiter already caps how fast one client can call
 * /users/login, but it counts by IP. An attacker rotating source addresses gets a
 * fresh budget per attempt, so that limiter alone does not stop credential stuffing
 * against a known email — it only makes the attack slower per connection. This keys
 * the counter on the account being targeted instead, so the limit survives the
 * attacker changing address.
 *
 * Only the account key blocks. An earlier version also locked on a per-IP count, to
 * stop anyone deliberately burning a real user's allowance; that turned out to punish
 * exactly the deployment this product is for. Several staff behind one NAT address
 * share the counter, so three people mistyping a password apiece locked each other
 * out, and the target never even had to try — they were simply collateral. The
 * account key already bounds the damage: an attacker can lock one account at a time,
 * for five minutes, and pays the route limiter for every guess. Flooding many
 * accounts from one host remains the route limiter's job.
 *
 * Per-IP attempts are still recorded, so the volume is visible for alerting without
 * deciding who gets in.
 */
class LoginThrottle
{
    /**
     * Failed attempts tolerated against one account before it is locked.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Window the failures accumulate over, and how long a lockout lasts. Decay matches
     * lockout: the counter forgets on its own after the same stretch, so a user who
     * waits out a lockout also starts with a clean counter.
     */
    public const DECAY_SECONDS = 300;

    /**
     * Seconds until the account may try again, or 0 when it is not locked.
     *
     * Gated on tooManyAttempts() rather than reading availableIn() directly:
     * availableIn() returns the timer key's remaining TTL, and that key is written by
     * the very first hit. Consulted alone it reported a lockout after one failed
     * attempt, so the second mistyped password was answered 429 and the user was
     * locked out of their own business having typed the password correctly once.
     */
    public function availableIn(string $identifier): int
    {
        $key = $this->accountKey($identifier);

        return RateLimiter::tooManyAttempts($key, static::MAX_ATTEMPTS)
            ? (int) RateLimiter::availableIn($key)
            : 0;
    }

    /**
     * Whether a login attempt should be refused before the password is even checked.
     */
    public function tooManyAttempts(string $identifier): bool
    {
        return RateLimiter::tooManyAttempts($this->accountKey($identifier), static::MAX_ATTEMPTS);
    }

    /**
     * Record one failure. Both keys are counted; only the account key can lock.
     */
    public function hit(string $identifier, string $ip): void
    {
        // maxAttempts parks the key at the ceiling instead of counting past it, which
        // stops a long-running attempt from inflating the counter forever.
        RateLimiter::hit($this->accountKey($identifier), static::DECAY_SECONDS, static::MAX_ATTEMPTS);
        RateLimiter::hit($this->ipKey($ip), static::DECAY_SECONDS);
    }

    /**
     * Forget both counters. Called on a successful login: the failures that led here
     * were the user's own typos, and letting them linger would lock them out right
     * after a typo they had just fixed.
     */
    public function clear(string $identifier, string $ip): void
    {
        RateLimiter::clear($this->accountKey($identifier));
        RateLimiter::clear($this->ipKey($ip));
    }

    /**
     * Attempts remaining against the account before lockout.
     */
    public function remaining(string $identifier): int
    {
        return RateLimiter::remaining($this->accountKey($identifier), static::MAX_ATTEMPTS);
    }

    /**
     * Failed attempts seen from one address, for alerting. Does not affect access.
     */
    public function ipAttempts(string $ip): int
    {
        return RateLimiter::attempts($this->ipKey($ip));
    }

    /**
     * Keyed on the identifier the caller typed, normalised so "Bob@x.com" and
     * "bob@x.com" cannot each hold their own budget. The password is never part of
     * the key — the lockout must survive the attacker eventually getting it right.
     */
    private function accountKey(string $identifier): string
    {
        return 'login-throttle:account:'.Str::lower(trim($identifier));
    }

    private function ipKey(string $ip): string
    {
        return 'login-throttle:ip:'.$ip;
    }
}
