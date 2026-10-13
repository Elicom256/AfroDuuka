<?php

namespace App\Support\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The correlation id every API failure can point back to.
 *
 * One request may be rendered by several layers (middleware, the exception renderer,
 * the response header). They all funnel through here so the id the user quotes on
 * screen is the same one the request log recorded. Generating on demand rather than
 * assuming the middleware ran keeps the renderer usable from tests and from paths that
 * never pass through the api middleware group.
 */
final class RequestId
{
    public const ATTRIBUTE = 'request_id';

    public const HEADER = 'X-Request-Id';

    public static function for(Request $request): string
    {
        $existing = $request->attributes->get(self::ATTRIBUTE);

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $generated = (string) Str::ulid();
        $request->attributes->set(self::ATTRIBUTE, $generated);

        return $generated;
    }

    /**
     * A caller-supplied id is only trusted when it looks like an opaque token; anything
     * longer, shorter or containing shell/log-hostile characters is replaced, so a
     * client cannot forge an id that collides with another request or inject into logs.
     */
    public static function fromHeader(?string $header): ?string
    {
        if ($header === null) {
            return null;
        }

        $trimmed = trim($header);

        if (preg_match('/^[A-Za-z0-9_\-]{8,64}$/', $trimmed) !== 1) {
            return null;
        }

        return $trimmed;
    }
}
