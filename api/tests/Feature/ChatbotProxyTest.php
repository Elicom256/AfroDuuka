<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The public landing-chatbot proxy.
 *
 * Two properties matter beyond "it works": the TinyFish key must never leave the
 * server, and the fetch side must not become an open web proxy for anyone who finds
 * the route. Both are asserted here, along with the upstream-failure mapping — the
 * whole reason the proxy exists rather than the browser calling TinyFish directly is
 * that a rate limit or a down upstream must arrive as clean JSON, not a 500.
 *
 * No database is touched: the controller only moves bytes, so Http::fake is the whole
 * fixture. The TinyFish key is set through config() rather than a real environment
 * value, so the tests never depend on a secret being present.
 */
class ChatbotProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Each test gets its own source address so the route-level throttle:api budget
        // (60/min in the in-memory cache store, which lives for the whole process)
        // cannot carry one test's requests into the next.
        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.'.random_int(1, 250),
        ]);
    }

    private function withKey(): void
    {
        config(['services.tinyfish.api_key' => 'test-tinyfish-key']);
    }

    public function test_search_without_a_configured_key_answers_503_rather_than_crashing(): void
    {
        config(['services.tinyfish.api_key' => null]);
        Http::fake();

        $this->postJson('/api/chatbot/search', ['query' => 'afroduka'])
            ->assertStatus(503)
            ->assertJsonStructure(['message']);

        Http::assertNothingSent();
    }

    public function test_search_requires_a_query(): void
    {
        $this->withKey();
        Http::fake();

        $this->postJson('/api/chatbot/search', [])
            ->assertStatus(422);
    }

    public function test_search_passes_ranked_results_through(): void
    {
        $this->withKey();
        Http::fake([
            'api.search.tinyfish.ai*' => Http::response([
                'query' => 'afroduka',
                'results' => [['title' => 'AfroDuuka', 'url' => 'https://afroduka.com', 'snippet' => '...']],
                'total_results' => 1,
                'page' => 0,
            ]),
        ]);

        $this->postJson('/api/chatbot/search', ['query' => 'afroduka'])
            ->assertOk()
            ->assertJsonPath('results.0.url', 'https://afroduka.com');

        // The key travels in the header only — it must never be part of a body or query.
        Http::assertSent(function (Request $request) {
            return $request->hasHeader('X-API-Key', 'test-tinyfish-key')
                && ! str_contains($request->url(), 'test-tinyfish-key');
        });
    }

    public function test_search_maps_a_rate_limit_to_429_and_keeps_retry_after(): void
    {
        $this->withKey();
        Http::fake([
            'api.search.tinyfish.ai*' => Http::response(['error' => 'rate limited'], 429, ['Retry-After' => '12']),
        ]);

        $this->postJson('/api/chatbot/search', ['query' => 'afroduka'])
            ->assertStatus(429)
            ->assertJsonPath('retry_after', '12');
    }

    public function test_search_does_not_forward_an_upstream_auth_failure_body(): void
    {
        $this->withKey();
        Http::fake([
            'api.search.tinyfish.ai*' => Http::response(['error' => 'API key abc123 is revoked'], 401),
        ]);

        $response = $this->postJson('/api/chatbot/search', ['query' => 'afroduka'])
            ->assertStatus(502);

        // The upstream body names the key; a visitor must never see it.
        $this->assertStringNotContainsString('abc123', $response->getContent());
    }

    public function test_search_maps_an_upstream_server_error_to_502(): void
    {
        $this->withKey();
        Http::fake([
            'api.search.tinyfish.ai*' => Http::response('boom', 500),
        ]);

        $this->postJson('/api/chatbot/search', ['query' => 'afroduka'])
            ->assertStatus(502);
    }

    public function test_search_survives_an_unreachable_upstream(): void
    {
        $this->withKey();
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: timeout');
        });

        $this->postJson('/api/chatbot/search', ['query' => 'afroduka'])
            ->assertStatus(504)
            ->assertJsonStructure(['message']);
    }

    public function test_fetch_refuses_urls_outside_afrodukas_domain(): void
    {
        $this->withKey();
        Http::fake();

        $this->postJson('/api/chatbot/fetch', ['urls' => ['https://example.com/private']])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_fetch_keeps_only_afroduka_urls_and_sends_them_upstream(): void
    {
        $this->withKey();
        Http::fake([
            'api.fetch.tinyfish.ai' => Http::response([
                'results' => [['url' => 'https://afroduka.com/', 'title' => 'AfroDuuka', 'text' => '# AfroDuuka']],
                'errors' => [],
            ]),
        ]);

        $this->postJson('/api/chatbot/fetch', [
            'urls' => ['https://afroduka.com/', 'https://evil.example.com/x', 'https://docs.afroduka.com/guide'],
        ])->assertOk()->assertJsonPath('results.0.url', 'https://afroduka.com/');

        Http::assertSent(function (Request $request) {
            $sent = $request->data()['urls'] ?? [];

            return $sent === ['https://afroduka.com/', 'https://docs.afroduka.com/guide'];
        });
    }

    public function test_fetch_without_a_key_answers_503(): void
    {
        config(['services.tinyfish.api_key' => null]);
        Http::fake();

        $this->postJson('/api/chatbot/fetch', ['urls' => ['https://afroduka.com/']])
            ->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_fetch_survives_an_unreachable_upstream(): void
    {
        $this->withKey();
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: timeout');
        });

        $this->postJson('/api/chatbot/fetch', ['urls' => ['https://afroduka.com/']])
            ->assertStatus(504);
    }
}
