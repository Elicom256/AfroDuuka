<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * An unauthenticated caller must get a 401, not a 500.
 *
 * Laravel's Authenticate middleware redirects guests to `route('login')` by default.
 * This application has no such route — it is a JSON API behind an SPA that renders its
 * own login screen — so every protected endpoint reached without a token threw
 * `RouteNotFoundException: Route [login] not defined` and answered 500.
 *
 * The 500 was not cosmetic. The SPA detects a dead session by the 401 that
 * store/app/authListener.ts watches for, so an expired token produced a server error
 * the listener ignored: the app kept issuing requests with a token that could never
 * work, and the owner was left on a dashboard that silently did nothing.
 */
class UnauthenticatedResponseTest extends TestCase
{
    public static function protectedEndpoints(): array
    {
        return [
            'products' => ['get', '/api/products'],
            'current user' => ['get', '/api/users/me'],
            'pos search' => ['get', '/api/pos/products/search?q=test'],
            'pos checkout' => ['post', '/api/pos/checkout'],
            'create business' => ['post', '/api/dashboard/business'],
            'sales' => ['get', '/api/sales/branch-sales'],
        ];
    }

    #[DataProvider('protectedEndpoints')]
    public function test_an_unauthenticated_request_is_refused_with_401(string $method, string $uri): void
    {
        $response = $method === 'post'
            ? $this->postJson($uri, [])
            : $this->getJson($uri);

        $response->assertStatus(401)->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_the_refusal_is_json_and_not_a_redirect(): void
    {
        // A 302 would send the SPA's XHR to the login screen and hand it an HTML page
        // where it expects JSON.
        $response = $this->getJson('/api/products');

        $this->assertSame(401, $response->status());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('content-type'));
    }

    public function test_public_reference_data_stays_reachable_without_a_token(): void
    {
        // The onboarding form needs these before an account exists. A blanket 401 here
        // would break signup, so this guards the other direction too.
        $this->getJson('/api/business-categories')->assertStatus(200);
        $this->getJson('/api/countries')->assertStatus(200);
    }
}