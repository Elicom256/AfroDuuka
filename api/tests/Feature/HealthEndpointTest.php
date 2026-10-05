<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * /api/health needs no token, so it is the one endpoint anyone on the internet can
 * ask. It used to answer with the raw driver exception, which handed out the host and
 * port it tried, the database name, and whatever the driver said about credentials or
 * authentication.
 *
 * It still has to answer a monitor honestly, so these pin both halves: a healthy
 * install says so, and a failing one says only that it failed while the cause stays in
 * the log.
 */
class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_healthy_install_reports_ok_without_a_token(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.cache', 'ok');
    }

    public function test_the_body_never_contains_a_driver_exception(): void
    {
        $body = $this->getJson('/api/health')->getContent();

        foreach (['SQLSTATE', 'pgsql', 'getMessage', 'Exception', 'password', 'host'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase(
                $leak,
                (string) $body,
                "[{$leak}] leaked from the unauthenticated health endpoint."
            );
        }
    }

    public function test_a_failing_dependency_reports_an_error_without_explaining_why(): void
    {
        Cache::shouldReceive('store')
            ->andThrow(new \RuntimeException('redis://cache.internal:6379 AUTH failed for user "duuka"'));

        $response = $this->getJson('/api/health');

        // 503 so a monitor can alarm on it.
        $response->assertStatus(503)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.cache', 'error');

        $body = (string) $response->getContent();

        $this->assertStringNotContainsString('cache.internal', $body);
        $this->assertStringNotContainsString('AUTH failed', $body);
        $this->assertStringNotContainsString('duuka', $body);

        // The cause is still recoverable by an operator, from the log.
        $this->assertStringContainsString('health check failed', (string) file_get_contents(
            storage_path('logs/laravel.log')
        ));
    }

    public function test_the_database_is_actually_reached(): void
    {
        // Guards the probe itself: a health check that stopped checking would still
        // report 'ok' for a database it never touched.
        DB::connection()->getPdo();

        $this->getJson('/api/health')->assertJsonPath('checks.database', 'ok');
    }
}
