<?php

namespace App\Support;

/**
 * Builds absolute frontend URLs from the configured base.
 *
 * `config('app.frontend_url')` is supplied per environment, and it has arrived both
 * with and without a trailing slash — the Render dashboard value carried one, which
 * turned every generated link into `//verify-email`. nginx still served the SPA (it
 * merges slashes for the file lookup and falls back to index.html), but the browser
 * address bar kept the doubled slash, and the SPA's router never matches
 * `//verify-email` against its `/verify-email` route, so the click landed on the
 * catch-all 404.
 *
 * Every frontend link goes through here so a trailing slash in the environment can
 * never produce a doubled separator again.
 */
class FrontendUrl
{
    public static function path(string $path = ''): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $base.'/'.ltrim($path, '/');
    }
}
