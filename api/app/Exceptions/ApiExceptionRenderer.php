<?php

namespace App\Exceptions;

use App\Support\Api\RequestId;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * The single renderer for every failure on an api route.
 *
 * The contract it enforces is deliberately narrow: `message` is always human, `code` is
 * a stable slug the SPA can branch on without matching copy, and `request_id` is always
 * present so a user can quote it. Nothing here ever echoes a model class, a SQLSTATE, a
 * file path or the word "Exception" — a stack trace is for the log, not the screen.
 *
 * It is one `Throwable` renderer rather than a stack of type-hinted closures for a
 * reason the previous code documented the hard way: a catch-all registered alongside
 * narrower renderers matched first and downgraded every policy refusal and validation
 * error into a 500. Doing the whole taxonomy in one ordered method means there is no
 * registration order to get wrong.
 */
final class ApiExceptionRenderer
{
    /**
     * @return JsonResponse|null Null lets Laravel fall back to its own default (used for
     *                           non-api requests, which this application does not render).
     */
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($e instanceof HttpResponseException) {
            // Already a fully-formed HTTP response; the caller built it on purpose.
            return null;
        }

        if ($e instanceof AuthenticationException) {
            return self::json($request, 401, 'Unauthenticated.', 'unauthenticated');
        }

        if ($e instanceof ValidationException) {
            return self::validation($e, $request);
        }

        if ($e instanceof ModelNotFoundException) {
            return self::json(
                $request,
                404,
                "We couldn't find that record. It may have been removed.",
                'not_found',
            );
        }

        if ($e instanceof QueryException) {
            return self::queryException($e, $request);
        }

        if ($e instanceof AuthorizationException) {
            return self::json($request, 403, self::authorizationMessage($e), 'forbidden');
        }

        if ($e instanceof ThrottleRequestsException) {
            return self::throttled($e, $request);
        }

        if ($e instanceof FeatureDisabledException) {
            return self::json(
                $request,
                403,
                $e->getMessage(),
                'feature_disabled',
                ['feature' => $e->feature],
            );
        }

        if ($e instanceof NotFoundHttpException) {
            return self::json($request, 404, self::notFoundMessage($e), 'not_found');
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return self::json($request, 405, "That action isn't available here.", 'method_not_allowed');
        }

        if ($e instanceof HttpExceptionInterface) {
            // Covers abort(403/404/405/409, 'a real message') across the controllers.
            // A message the controller wrote is kept verbatim; only an empty or
            // framework-stock message is replaced.
            $status = $e->getStatusCode();

            return self::json(
                $request,
                $status,
                $e->getMessage() !== '' ? $e->getMessage() : self::fallbackForStatus($status),
                self::codeForStatus($status),
                [],
                $e->getHeaders(),
            );
        }

        report($e);

        return self::json(
            $request,
            500,
            'Something went wrong on our side. Please try again.',
            'server_error',
        );
    }

    private static function validation(ValidationException $e, Request $request): JsonResponse
    {
        $first = collect($e->errors())->flatten()->first();

        return self::json(
            $request,
            $e->status,
            $first !== null ? (string) $first : $e->getMessage(),
            'validation_error',
            ['errors' => $e->errors()],
        );
    }

    private static function queryException(QueryException $e, Request $request): JsonResponse
    {
        $mapped = self::mapSqlState(self::sqlState($e));

        if ($mapped === null) {
            report($e);

            return self::json(
                $request,
                500,
                'Something went wrong on our side. Please try again.',
                'server_error',
            );
        }

        return self::json($request, $mapped['status'], $mapped['message'], $mapped['code']);
    }

    private static function throttled(ThrottleRequestsException $e, Request $request): JsonResponse
    {
        $message = $e->getMessage() !== '' ? $e->getMessage() : 'Too many requests. Please wait a moment and try again.';

        return self::json($request, 429, $message, 'too_many_requests', [], $e->getHeaders());
    }

    /**
     * Translate a database constraint violation into something a person can act on.
     *
     * These are the failures that used to reach the user as "An unexpected error
     * occurred.": a duplicate account, a stale foreign key, a missing required value.
     *
     * @return array{status:int,message:string,code:string}|null
     */
    public static function mapSqlState(?string $sqlState): ?array
    {
        return match ($sqlState) {
            '23505' => [
                'status' => 409,
                'message' => 'A record with those details already exists.',
                'code' => 'duplicate',
            ],
            '23503' => [
                'status' => 409,
                'message' => 'That change depends on a record that no longer exists.',
                'code' => 'reference_missing',
            ],
            '23502' => [
                'status' => 422,
                'message' => 'A required value was missing. Please check the form and try again.',
                'code' => 'missing_value',
            ],
            '23514' => [
                'status' => 422,
                'message' => "One of the values you entered isn't allowed.",
                'code' => 'invalid_value',
            ],
            '22P02' => [
                'status' => 422,
                'message' => 'One of the values had the wrong format.',
                'code' => 'invalid_format',
            ],
            default => null,
        };
    }

    private static function sqlState(QueryException $e): ?string
    {
        $fromErrorInfo = $e->errorInfo[0] ?? null;

        if (is_string($fromErrorInfo) && $fromErrorInfo !== '') {
            return $fromErrorInfo;
        }

        $code = (string) $e->getCode();

        return $code !== '' && $code !== '0' ? $code : null;
    }

    private static function authorizationMessage(AuthorizationException $e): string
    {
        $message = trim($e->getMessage());

        // The framework's stock line names no subject; a policy that wrote its own
        // sentence keeps it.
        if ($message === '' || $message === 'This action is unauthorized.') {
            return "You don't have permission to do that.";
        }

        return $message;
    }

    private static function notFoundMessage(NotFoundHttpException $e): string
    {
        $message = trim($e->getMessage());

        if ($message === '' || $message === 'Not Found' || $message === 'Not found') {
            return "We couldn't find what you were looking for.";
        }

        return $message;
    }

    private static function fallbackForStatus(int $status): string
    {
        return match (true) {
            $status === 400 => 'That request could not be processed.',
            $status === 401 => 'Your session has ended. Please sign in again.',
            $status === 403 => "You don't have permission to do that.",
            $status === 404 => "We couldn't find what you were looking for.",
            $status === 405 => "That action isn't available here.",
            $status === 409 => 'That change conflicts with something else. Refresh and try again.',
            $status === 419 => 'Your session expired. Please refresh and try again.',
            $status === 422 => 'Some details need fixing. Please check and try again.',
            $status === 429 => 'Too many requests. Please wait a moment and try again.',
            $status >= 500 => 'Something went wrong on our side. Please try again.',
            default => 'That request could not be completed.',
        };
    }

    private static function codeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            409 => 'conflict',
            419 => 'session_expired',
            422 => 'unprocessable',
            429 => 'too_many_requests',
            default => $status >= 500 ? 'server_error' : 'http_error',
        };
    }

    /**
     * @param  array<string,mixed>  $extra
     * @param  array<string,string>  $headers
     */
    private static function json(
        Request $request,
        int $status,
        string $message,
        string $code,
        array $extra = [],
        array $headers = [],
    ): JsonResponse {
        $id = RequestId::for($request);

        return response()->json(array_merge([
            'message' => $message,
            'code' => $code,
            'request_id' => $id,
        ], $extra), $status, array_merge([RequestId::HEADER => $id], $headers));
    }
}
