<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * A thin, public, read-only gateway to TinyFish for the landing-page chatbot.
 *
 * The chatbot answers public questions about AfroDuuka, so this exists only to keep
 * the TinyFish API key server-side: the browser calls these routes and never sees the
 * key. Two operations, both pass-through:
 *
 *   search — GET  api.search.tinyfish.ai, ranked results (free at any balance)
 *   fetch  — POST api.fetch.tinyfish.ai, clean markdown for known URLs
 *
 * Fetch is limited to AfroDuuka's own domain. Left open, this endpoint would be an
 * open web proxy for anyone on the internet, so only afroduuka.com pages are retrieved.
 *
 * Nothing here composes an answer and nothing here touches a model — that is the
 * chatbot's job on the frontend. This only moves bytes and maps upstream failures onto
 * clean JSON so a rate limit or a down upstream never surfaces as a 500 or a trace.
 */
class ChatbotController extends Controller
{
    private const SEARCH_URL = 'https://api.search.tinyfish.ai';

    private const FETCH_URL = 'https://api.fetch.tinyfish.ai';

    /** Only AfroDuuka's own public site is fetched through this proxy. */
    private const ALLOWED_FETCH_HOST = 'afroduka.com';

    /** Upstream timeouts, matching TinyFish's documented latency guidance. */
    private const SEARCH_TIMEOUT = 10;

    private const FETCH_TIMEOUT = 30;

    /**
     * Ranked web results for a query.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:500',
        ]);

        if (! $this->apiKey()) {
            return $this->notConfigured();
        }

        try {
            $response = Http::timeout(self::SEARCH_TIMEOUT)
                ->withHeaders(['X-API-Key' => $this->apiKey()])
                ->get(self::SEARCH_URL, [
                    'query' => $request->input('query'),
                    'language' => 'en',
                ]);
        } catch (ConnectionException) {
            return $this->unreachable();
        }

        return $this->proxy($response);
    }

    /**
     * Clean page content for a set of URLs, restricted to AfroDuuka's domain.
     */
    public function fetch(Request $request): JsonResponse
    {
        $request->validate([
            'urls' => 'required|array|min:1|max:10',
            'urls.*' => 'string',
        ]);

        $urls = $this->allowedUrls($request->input('urls'));

        if ($urls === []) {
            return response()->json([
                'message' => 'No supported pages were provided. I can only read afroduka.com pages.',
            ], 422);
        }

        if (! $this->apiKey()) {
            return $this->notConfigured();
        }

        try {
            $response = Http::timeout(self::FETCH_TIMEOUT)
                ->withHeaders(['X-API-Key' => $this->apiKey()])
                ->post(self::FETCH_URL, [
                    'urls' => $urls,
                    'format' => 'markdown',
                    'links' => true,
                ]);
        } catch (ConnectionException) {
            return $this->unreachable();
        }

        return $this->proxy($response);
    }

    /**
     * The configured key, or null when it has not been set.
     */
    private function apiKey(): ?string
    {
        return config('services.tinyfish.api_key') ?: null;
    }

    /**
     * Filter the requested URLs down to AfroDuuka's own public site.
     *
     * Per-URL failures upstream are reported in the response's errors[] and do not fail
     * the batch, so a single bad page among good ones still returns the good ones.
     */
    private function allowedUrls(array $urls): array
    {
        $allowed = [];

        foreach ($urls as $url) {
            $scheme = parse_url((string) $url, PHP_URL_SCHEME);
            $host = parse_url((string) $url, PHP_URL_HOST);

            if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '') {
                continue;
            }

            if ($host === self::ALLOWED_FETCH_HOST || str_ends_with($host, '.'.self::ALLOWED_FETCH_HOST)) {
                $allowed[] = (string) $url;
            }
        }

        return $allowed;
    }

    /**
     * Map an upstream response onto a clean chatbot-friendly JSON envelope.
     *
     * A 429 is passed through with its Retry-After so the client can back off. A 401/403
     * is deliberately not forwarded: the upstream body names the key or account, and
     * there is nothing a visitor can do about a server misconfiguration. Everything else
     * that is not a 2xx becomes a plain, retryable message.
     */
    private function proxy($response): JsonResponse
    {
        $status = $response->status();

        if ($status === 429) {
            return response()->json([
                'message' => 'I hit a rate limit while looking that up. Please try again shortly.',
                'retry_after' => $response->header('Retry-After'),
            ], 429);
        }

        if ($status === 401 || $status === 403 || $status >= 500) {
            return response()->json([
                'message' => 'I could not complete that web lookup. Please try again shortly.',
            ], 502);
        }

        if ($status >= 400) {
            return response()->json([
                'message' => 'That web lookup was not accepted. Please try a different question.',
            ], 502);
        }

        return response()->json($response->json() ?? []);
    }

    /**
     * A missing key is a configuration problem, not a broken request — say so plainly
     * rather than throwing a 500 with a stack trace.
     */
    private function notConfigured(): JsonResponse
    {
        return response()->json([
            'message' => 'Web lookup is not configured yet. Please try again later.',
        ], 503);
    }

    /**
     * The upstream never answered — DNS, connection refused, or the timeout elapsed.
     * Laravel's HTTP client raises ConnectionException for all three; left uncaught it
     * surfaces as a 500 with a stack trace on a public landing page.
     */
    private function unreachable(): JsonResponse
    {
        return response()->json([
            'message' => 'I could not reach the web lookup service. Please try again shortly.',
        ], 504);
    }
}
