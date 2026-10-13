<?php

namespace Tests\Unit;

use App\Http\Middleware\AssignRequestId;
use App\Support\Api\RequestId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AssignRequestIdMiddlewareTest extends TestCase
{
    public function test_it_generates_a_correlation_id_and_returns_it_as_a_header(): void
    {
        $request = Request::create('/api/products', 'GET');

        $response = (new AssignRequestId)->handle($request, fn () => response()->json(['ok' => true]));

        $id = $response->headers->get(RequestId::HEADER);

        $this->assertNotNull($id);
        $this->assertSame(26, strlen($id), 'expected a ULID');
        $this->assertSame($id, $request->attributes->get(RequestId::ATTRIBUTE));
    }

    public function test_it_reuses_a_well_formed_inbound_id(): void
    {
        $request = Request::create('/api/products', 'GET', [], [], [], [
            'HTTP_X_REQUEST_ID' => '01J8ZZZZZZZZZZZZZZZZZZZZZZ',
        ]);

        $response = (new AssignRequestId)->handle($request, fn () => response()->json([]));

        $this->assertSame('01J8ZZZZZZZZZZZZZZZZZZZZZZ', $response->headers->get(RequestId::HEADER));
    }

    public function test_it_replaces_a_hostile_inbound_id(): void
    {
        $request = Request::create('/api/products', 'GET', [], [], [], [
            'HTTP_X_REQUEST_ID' => "evil\ninjected",
        ]);

        $response = (new AssignRequestId)->handle($request, fn () => response()->json([]));

        $this->assertNotSame("evil\ninjected", $response->headers->get(RequestId::HEADER));
        $this->assertSame(26, strlen((string) $response->headers->get(RequestId::HEADER)));
    }

    public function test_a_rejected_request_is_logged_at_warning(): void
    {
        Log::spy();

        $request = Request::create('/api/products', 'GET');

        (new AssignRequestId)->handle($request, fn () => response()->json(['message' => 'nope'], 422));

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_server_error_is_logged_at_error(): void
    {
        Log::spy();

        $request = Request::create('/api/products', 'GET');

        (new AssignRequestId)->handle($request, fn () => response()->json([], 500));

        Log::shouldHaveReceived('error')->once();
    }
}
