<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards against frontend/backend API drift.
 *
 * Every RTK Query endpoint in ui/src/app/store/features/ must resolve to a real
 * backend route with a compatible HTTP method. This catches the class of bug
 * where the backend prefix was renamed (admin → dashboard) and the frontend
 * was never updated, and where a mutation sends the wrong verb (DELETE to an
 * update endpoint, etc.).
 */
class FrontendApiPathsTest extends TestCase
{
    public function test_every_frontend_api_path_resolves_to_a_backend_route(): void
    {
        $frontendDir = '/frontend/src/app/store/features';

        $files = \Symfony\Component\Finder\Finder::create()
            ->files()
            ->in($frontendDir)
            ->name('*.ts');

        $checked = 0;
        $errors = [];

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());

            preg_match_all(
                '/baseUrl:\s*`\$\{import\.meta\.env\.VITE_BASE_URL\}([^`]+)`/',
                $content,
                $baseUrlMatches,
                PREG_OFFSET_CAPTURE
            );

            if (empty($baseUrlMatches[0])) {
                continue;
            }

            preg_match_all(
                '/url:\s*[\'"`]([^\'"`]+)[\'"`],\s*method:\s*[\'"`]([A-Z]+)[\'"`]/',
                $content,
                $endpointMatches,
                PREG_OFFSET_CAPTURE
            );

            foreach ($endpointMatches[0] as $i => $endpointMatch) {
                $url = $endpointMatches[1][$i][0];
                $method = $endpointMatches[2][$i][0];
                $endpointPos = $endpointMatch[1];

                $baseUrl = null;
                foreach ($baseUrlMatches[0] as $j => $baseUrlMatch) {
                    if ($baseUrlMatch[1] < $endpointPos) {
                        $baseUrl = $baseUrlMatches[1][$j][0];
                    } else {
                        break;
                    }
                }

                if ($baseUrl === null) {
                    continue;
                }

                $url = preg_replace('/\$\{[^}]+\}/', '1', $url);
                $url = parse_url($url, PHP_URL_PATH) ?: '/';

                $fullPath = 'api' . rtrim($baseUrl, '/') . $url;
                $fullPath = preg_replace('#/+#', '/', $fullPath);
                $fullPath = rtrim($fullPath, '/');

                if (!$this->routeExists($fullPath, $method)) {
                    $errors[] = sprintf(
                        '  %s: %s %s%s → %s',
                        $file->getFilename(),
                        $method,
                        $baseUrl,
                        $url,
                        $fullPath
                    );
                }

                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked, 'No frontend API endpoints were found to check');
        $this->assertEmpty($errors, "Frontend API paths that don't match backend routes:\n" . implode("\n", $errors));
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
