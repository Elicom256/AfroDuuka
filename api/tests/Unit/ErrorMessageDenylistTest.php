<?php

namespace Tests\Unit;

use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PDOException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Denylist test — the rendered error `message` must never contain:
 *  - Any model FQCN (e.g. `App\Models\Product`)
 *  - Any SQLSTATE code (e.g. `42703`, `23505`)
 *  - Any file path (`/home/`, `/var/www/`, `vendor/`, `src/`)
 *  - The word `Exception` (class names or the word itself)
 *  - The word `PDOException`, `QueryException`, `ModelNotFoundException`, etc.
 *  - Stack trace markers (`#0`, `at `, `in `, `on line`)
 *
 * This test covers every renderer branch by feeding it representative exceptions
 * and asserting the output message is clean.
 */
class ErrorMessageDenylistTest extends TestCase
{
    private function apiRequest(): Request
    {
        return Request::create('/api/test', 'GET');
    }

    private function body(\Illuminate\Http\JsonResponse $response): array
    {
        return json_decode((string) $response->getContent(), true);
    }

    /**
     * @param array{type: string, data: array} $case
     */
    #[DataProvider('denylistProvider')]
    public function test_message_never_contains_forbidden_strings(string $description, array $case): void
    {
        $exception = match ($case['type']) {
            'model_not_found' => (new ModelNotFoundException())->setModel('App\\Models\\Product', [5]),
            'validation' => ValidationException::withMessages($case['data']['messages']),
            'query' => $this->makeQueryException($case['data']['sqlState']),
            'authorization_default' => new \Illuminate\Auth\Access\AuthorizationException,
            'authorization_custom' => new \Illuminate\Auth\Access\AuthorizationException($case['data']['message']),
            'http_405' => new HttpException(405, $case['data']['message']),
            'http_403' => new HttpException(403, $case['data']['message']),
            'runtime_sqlstate' => new RuntimeException('SQLSTATE[42703]: Undefined column: app_private.secret'),
            'runtime_path' => new RuntimeException('Failed at /home/user/project/src/Service.php on line 42'),
            'runtime_stack' => new RuntimeException('Error #0 at /var/www/html/vendor/laravel/framework/src/Illuminate/Database/Connection.php on line 857'),
            default => throw new \InvalidArgumentException("Unknown case type: {$case['type']}"),
        };

        $response = ApiExceptionRenderer::render($exception, $this->apiRequest());
        $body = $this->body($response);
        $message = $body['message'] ?? '';

        // Forbidden patterns
        $this->assertStringNotContainsString('App\\Models', $message, $description);
        $this->assertStringNotContainsString('App\Models', $message, $description);
        $this->assertStringNotContainsString('SQLSTATE', $message, $description);
        $this->assertStringNotContainsString('driver detail', $message, $description);
        $this->assertStringNotContainsString('insert into', $message, $description);
        $this->assertStringNotContainsString('Exception', $message, $description);
        $this->assertStringNotContainsString('PDOException', $message, $description);
        $this->assertStringNotContainsString('QueryException', $message, $description);
        $this->assertStringNotContainsString('ModelNotFoundException', $message, $description);
        $this->assertStringNotContainsString('ValidationException', $message, $description);
        $this->assertStringNotContainsString('RuntimeException', $message, $description);
        $this->assertStringNotContainsString('HttpException', $message, $description);
        $this->assertStringNotContainsString('AuthorizationException', $message, $description);
        $this->assertStringNotContainsString('AuthenticationException', $message, $description);
        $this->assertStringNotContainsString('NotFoundHttpException', $message, $description);
        $this->assertStringNotContainsString('MethodNotAllowedHttpException', $message, $description);
        $this->assertStringNotContainsString('ThrottleRequestsException', $message, $description);
        $this->assertStringNotContainsString('/home/', $message, $description);
        $this->assertStringNotContainsString('/var/www/', $message, $description);
        $this->assertStringNotContainsString('vendor/', $message, $description);
        $this->assertStringNotContainsString('src/', $message, $description);
        $this->assertStringNotContainsString('#0', $message, $description);
        $this->assertStringNotContainsString('at /', $message, $description);
        $this->assertStringNotContainsString('at #', $message, $description);
        $this->assertStringNotContainsString('in /', $message, $description);
        $this->assertStringNotContainsString('on line', $message, $description);
    }

    private function makeQueryException(string $sqlState): QueryException
    {
        $pdo = new PDOException('constraint failed');
        $pdo->errorInfo = [$sqlState, 7, 'driver detail that must never reach the user'];

        return new QueryException('pgsql', 'insert into "users" ...', [], $pdo);
    }

    public static function denylistProvider(): array
    {
        return [
            // ModelNotFoundException — must not leak model class
            'model not found' => [
                'ModelNotFoundException with model name',
                ['type' => 'model_not_found', 'data' => []],
            ],

            // ValidationException — must not leak exception class
            'validation' => [
                'ValidationException with field messages',
                ['type' => 'validation', 'data' => ['messages' => ['name' => ['The name field is required.']]]],
            ],

            // QueryException with various SQLSTATEs — must not leak SQLSTATE or driver detail
            'query unique (23505)' => [
                'QueryException unique (23505)',
                ['type' => 'query', 'data' => ['sqlState' => '23505']],
            ],
            'query foreign key (23503)' => [
                'QueryException FK (23503)',
                ['type' => 'query', 'data' => ['sqlState' => '23503']],
            ],
            'query not null (23502)' => [
                'QueryException not null (23502)',
                ['type' => 'query', 'data' => ['sqlState' => '23502']],
            ],
            'query check (23514)' => [
                'QueryException check (23514)',
                ['type' => 'query', 'data' => ['sqlState' => '23514']],
            ],
            'query bad format (22P02)' => [
                'QueryException format (22P02)',
                ['type' => 'query', 'data' => ['sqlState' => '22P02']],
            ],
            'query unrecognised (42601)' => [
                'QueryException unknown (42601)',
                ['type' => 'query', 'data' => ['sqlState' => '42601']],
            ],

            // AuthorizationException — must not leak default message
            'authorization default' => [
                'AuthorizationException (stock message)',
                ['type' => 'authorization_default', 'data' => []],
            ],
            'authorization custom' => [
                'AuthorizationException (custom policy message)',
                ['type' => 'authorization_custom', 'data' => ['message' => 'Only the owner can approve payroll.']],
            ],

            // HttpException — must keep the written message, not leak the class
            'http 405' => [
                'HttpException 405 with message',
                ['type' => 'http_405', 'data' => ['message' => 'Sales cannot be deleted once stock movements have been recorded.']],
            ],
            'http 403' => [
                'HttpException 403 with message',
                ['type' => 'http_403', 'data' => ['message' => 'This action is unauthorized.']],
            ],

            // Unexpected exception — must become generic server_error
            'unexpected runtime sqlstate' => [
                'RuntimeException with SQLSTATE in message',
                ['type' => 'runtime_sqlstate', 'data' => []],
            ],
            'unexpected runtime path' => [
                'RuntimeException with file path',
                ['type' => 'runtime_path', 'data' => []],
            ],
            'unexpected runtime stack' => [
                'RuntimeException with #0 at',
                ['type' => 'runtime_stack', 'data' => []],
            ],
        ];
    }
}