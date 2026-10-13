<?php

namespace App\Http\Middleware;

use App\Support\Api\RequestId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Stamps every API request with a correlation id and logs one structured line for it.
 *
 * The id is set on the request *before* the rest of the stack runs, so the exception
 * renderer can quote the same value in the failure body and the response carries it as
 * `X-Request-Id`. That is what lets a user read a reference off the screen and have
 * support find the exact request — which is the whole point of the readable-error work.
 *
 * The log line deliberately records shape, not content: method, path, status, duration,
 * user and business. Request bodies and tokens are never logged.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = RequestId::fromHeader($request->header(RequestId::HEADER))
            ?? (string) Str::ulid();

        $request->attributes->set(RequestId::ATTRIBUTE, $id);

        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            // The exception renderer builds the final response, so the real status is
            // not known here. Log the exception class now and rethrow.
            $this->log($request, $id, $startedAt, 500, $e);

            throw $e;
        }

        $response->headers->set(RequestId::HEADER, $id);

        $this->log($request, $id, $startedAt, $response->getStatusCode());

        return $response;
    }

    private function log(
        Request $request,
        string $id,
        float $startedAt,
        int $status,
        ?Throwable $exception = null,
    ): void {
        $context = [
            'request_id' => $id,
            'method' => $request->getMethod(),
            'path' => '/'.ltrim($request->path(), '/'),
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'business_id' => optional($request->user())->business_id,
        ];

        if ($exception !== null) {
            $context['exception_class'] = $exception::class;
        }

        if ($status >= 500) {
            Log::error('api.request.failed', $context);
        } elseif ($status >= 400) {
            Log::warning('api.request.rejected', $context);
        } else {
            Log::debug('api.request', $context);
        }
    }
}
