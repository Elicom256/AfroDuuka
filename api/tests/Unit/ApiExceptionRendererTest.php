<?php

namespace Tests\Unit;

use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * The API failure contract: human `message`, stable `code`, always a `request_id`, and
 * never a leaked model class, SQLSTATE or exception text on screen.
 */
class ApiExceptionRendererTest extends TestCase
{
    private function apiRequest(): Request
    {
        return Request::create('/api/products', 'GET');
    }

    /** @return array<string,mixed> */
    private function body(JsonResponse $response): array
    {
        return json_decode((string) $response->getContent(), true);
    }

    private function queryException(string $sqlState): QueryException
    {
        $pdo = new PDOException('constraint failed');
        $pdo->errorInfo = [$sqlState, 7, 'driver detail that must never reach the user'];

        return new QueryException('pgsql', 'insert into "users" ...', [], $pdo);
    }

    public function test_it_ignores_non_api_requests(): void
    {
        $request = Request::create('/web/somewhere', 'GET');

        $this->assertNull(ApiExceptionRenderer::render(new RuntimeException('boom'), $request));
    }

    public function test_model_not_found_is_a_friendly_404_without_the_class_name(): void
    {
        $response = ApiExceptionRenderer::render(
            (new ModelNotFoundException)->setModel('App\\Models\\Product', [5]),
            $this->apiRequest(),
        );

        $body = $this->body($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('not_found', $body['code']);
        $this->assertNotEmpty($body['request_id']);
        $this->assertStringNotContainsString('App\\Models', $body['message']);
        $this->assertStringNotContainsString('Product', $body['message']);
    }

    public function test_unauthenticated_message_is_preserved(): void
    {
        $response = ApiExceptionRenderer::render(new AuthenticationException, $this->apiRequest());

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthenticated.', $this->body($response)['message']);
    }

    public function test_validation_leads_with_the_first_field_message_and_keeps_the_map(): void
    {
        $exception = ValidationException::withMessages([
            'name' => ['The name field is required.'],
            'phone' => ['The phone number looks too short.'],
        ]);

        $body = $this->body(ApiExceptionRenderer::render($exception, $this->apiRequest()));

        $this->assertSame('validation_error', $body['code']);
        $this->assertSame('The name field is required.', $body['message']);
        $this->assertArrayHasKey('phone', $body['errors']);
        $this->assertNotEmpty($body['request_id']);
    }

    #[DataProvider('constraintProvider')]
    public function test_constraint_violations_map_to_readable_statuses(string $sqlState, int $status, string $code): void
    {
        $response = ApiExceptionRenderer::render($this->queryException($sqlState), $this->apiRequest());
        $body = $this->body($response);

        $this->assertSame($status, $response->getStatusCode(), $sqlState);
        $this->assertSame($code, $body['code'], $sqlState);
        $this->assertStringNotContainsString('SQLSTATE', $body['message']);
        $this->assertStringNotContainsString('driver detail', $body['message']);
        $this->assertStringNotContainsString('insert into', $body['message']);
    }

    public static function constraintProvider(): array
    {
        return [
            'unique' => ['23505', 409, 'duplicate'],
            'foreign key' => ['23503', 409, 'reference_missing'],
            'not null' => ['23502', 422, 'missing_value'],
            'check' => ['23514', 422, 'invalid_value'],
            'bad format' => ['22P02', 422, 'invalid_format'],
        ];
    }

    public function test_an_unrecognised_sqlstate_is_a_generic_500_without_driver_detail(): void
    {
        $body = $this->body(ApiExceptionRenderer::render($this->queryException('42601'), $this->apiRequest()));

        $this->assertSame('server_error', $body['code']);
        $this->assertStringNotContainsString('driver detail', $body['message']);
        $this->assertStringNotContainsString('SQLSTATE', $body['message']);
    }

    public function test_the_stock_authorization_message_is_replaced(): void
    {
        $body = $this->body(ApiExceptionRenderer::render(new AuthorizationException, $this->apiRequest()));

        $this->assertSame('forbidden', $body['code']);
        $this->assertSame("You don't have permission to do that.", $body['message']);
    }

    public function test_a_policy_message_written_by_hand_is_kept(): void
    {
        $body = $this->body(ApiExceptionRenderer::render(
            new AuthorizationException('Only the owner can approve payroll.'),
            $this->apiRequest(),
        ));

        $this->assertSame('Only the owner can approve payroll.', $body['message']);
    }

    public function test_an_abort_message_is_kept_and_the_envelope_is_added(): void
    {
        $response = ApiExceptionRenderer::render(
            new HttpException(405, 'Sales cannot be deleted once stock movements have been recorded.'),
            $this->apiRequest(),
        );

        $body = $this->body($response);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('method_not_allowed', $body['code']);
        $this->assertSame('Sales cannot be deleted once stock movements have been recorded.', $body['message']);
        $this->assertNotEmpty($body['request_id']);
    }

    public function test_an_unexpected_failure_never_leaks_the_exception_text(): void
    {
        $body = $this->body(ApiExceptionRenderer::render(
            new RuntimeException('SQLSTATE[42703]: Undefined column: app_private.secret'),
            $this->apiRequest(),
        ));

        $this->assertSame('server_error', $body['code']);
        $this->assertSame('Something went wrong on our side. Please try again.', $body['message']);
    }
}
