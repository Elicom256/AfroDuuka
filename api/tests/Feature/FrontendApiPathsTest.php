<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Guards against frontend/backend API drift.
 *
 * Every RTK Query endpoint in ui/src/app/store/features/ must resolve to a real
 * backend route with a compatible HTTP method. This catches the class of bug
 * where the backend prefix was renamed (admin → dashboard) and the frontend
 * was never updated, and where a mutation sends the wrong verb (DELETE to an
 * update endpoint, etc.).
 *
 * The endpoints no longer carry a per-endpoint `baseUrl`; they were refactored onto a
 * single shared base query, so the `url` already contains the full path (e.g.
 * /dashboard/roles) and only needs the `api` prefix. Two shapes are recognised:
 *
 *   query: () => ({ url: '/dashboard/roles', method: 'GET' })
 *   query: () => '/dashboard/business'        // bare string, implicitly GET
 */
class FrontendApiPathsTest extends TestCase
{
    public function test_every_frontend_api_path_resolves_to_a_backend_route(): void
    {
        $frontendDir = '/frontend/src/app/store/features';

        $files = Finder::create()
            ->files()
            ->in($frontendDir)
            ->name('*.ts');

        $checked = 0;
        $errors = [];

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());

            $endpoints = [];

            // Object form: { url: '...', method: '...' }. The /s flag lets url and
            // method sit on separate lines, which the multi-line endpoints do.
            if (preg_match_all(
                '/url:\s*([`\'"])([^`\'"]+)\1\s*,\s*method:\s*([`\'"])([A-Z]+)\3/s',
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                foreach ($matches as $match) {
                    $endpoints[] = ['url' => $match[2], 'method' => $match[4]];
                }
            }

            // Bare form: query: () => '/path' — no method, so it is a GET.
            if (preg_match_all(
                '/query:\s*\([^)]*\)\s*=>\s*([`\'"])(\/[^`\'"]*)\1/s',
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                foreach ($matches as $match) {
                    $endpoints[] = ['url' => $match[2], 'method' => 'GET'];
                }
            }

            foreach ($endpoints as $endpoint) {
                $url = preg_replace('/\$\{[^}]+\}/', '1', $endpoint['url']);
                $url = parse_url($url, PHP_URL_PATH) ?: '/';

                $fullPath = 'api/'.ltrim($url, '/');
                $fullPath = preg_replace('#/+#', '/', $fullPath);
                $fullPath = rtrim($fullPath, '/');

                if (! $this->routeExists($fullPath, $endpoint['method'])) {
                    $errors[] = sprintf(
                        '  %s: %s %s → %s',
                        $file->getFilename(),
                        $endpoint['method'],
                        $endpoint['url'],
                        $fullPath
                    );
                }

                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked, 'No frontend API endpoints were found to check');
        $this->assertEmpty($errors, "Frontend API paths that don't match backend routes:\n".implode("\n", $errors));
    }

    private function routeExists(string $path, string $method): bool
    {
        foreach (Route::getRoutes() as $route) {
            $routePath = $route->uri();
            $routePath = preg_replace('/\{[^}]+\}/', '1', $routePath);

            if ($routePath !== $path) {
                continue;
            }

            $methods = $route->methods();
            if (in_array($method, $methods)) {
                return true;
            }
            if ($method === 'GET' && in_array('HEAD', $methods)) {
                return true;
            }
        }

        return false;
    }
}
