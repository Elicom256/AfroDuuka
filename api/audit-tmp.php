<?php
// Throwaway audit harness: walks the real route table and reports mutating routes
// with no resolvable authorization gate. Used to get the authoritative gap list.

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

$ref = new ReflectionClass(Illuminate\Routing\Route::class);

function routeUri($route) { return $route->uri(); }
function routeMethods($route) { return $route->methods(); }
function routeMiddleware($route) { return $route->gatherMiddleware(); }
function routeAction($route) {
    return $route->getAction('uses');
}

function bodyOf(string $file, int $start, int $end): string
{
    $lines = File::get($file);
    return implode("\n", array_slice(explode("\n", $lines), $start - 1, $end - $start + 1));
}

function classFile(string $class): ?string
{
    try { return (new ReflectionClass($class))->getFileName() ?: null; }
    catch (\Throwable) { return null; }
}

function controllerClass($action): ?string
{
    if (is_string($action) && str_contains($action, '@')) {
        return Str::before($action, '@');
    }
    if (is_array($action) && isset($action[0])) {
        return is_object($action[0]) ? get_class($action[0]) : (string) $action[0];
    }
    return null;
}

// Markers that constitute a real authorization decision.
function gateMarkers(string $body): array
{
    $found = [];
    if (str_contains($body, 'RolePermissions::')) {
        $found[] = 'RolePermissions';
    }
    if (preg_match('/->\s*authorize\s*\(/', $body)) {
        $found[] = '$this->authorize';
    }
    if (preg_match('/\bGate::/', $body)) {
        $found[] = 'Gate::';
    }
    if (preg_match('/\bPolicies?\\\\/', $body)) {
        $found[] = 'Policy class';
    }
    // ad-hoc role arrays in abort()/abort_unless(), e.g. FinanceController
    if (preg_match('/\babort(_unless|_if)?\s*\(/i', $body) && preg_match('/role/i', $body)) {
        $found[] = 'abort+role';
    }
    return $found;
}

$reflections = [];

function methodBodies(string $class): array
{
    global $reflections;
    if (isset($reflections[$class])) {
        return $reflections[$class];
    }
    $out = [];
    $file = classFile($class);
    if ($file === null) {
        return $reflections[$class] = [];
    }
    $rc = new ReflectionClass($class);
    foreach ($rc->getMethods() as $m) {
        $out[$m->getName()] = bodyOf($file, $m->getStartLine(), $m->getEndLine());
    }
    return $reflections[$class] = $out;
}

/** Resolve a gate inside one method, recursing into $this->helper() calls. */
function resolveMethod(string $class, string $method, array &$chain = []): array
{
    $methods = methodBodies($class);
    if (!isset($methods[$method])) {
        return [];
    }
    $body = $methods[$method];
    $markers = gateMarkers($body);
    if ($markers) {
        return $markers;
    }

    foreach ($chain as $seen) {
        if ($seen === $method) {
            return [];
        }
    }
    $chain[] = $method;

    // Delegated helper: $this->ensureSomething(...)
    if (preg_match_all('/\$this->(\w+)\s*\(/', $body, $m)) {
        foreach ($m[1] as $helper) {
            $found = resolveMethod($class, $helper, $chain);
            if ($found) {
                return ['helper:'.$helper.'() => '.implode(',', $found)];
            }
        }
    }
    return [];
}

/** Does a FormRequest class carry a real gate in authorize()? */
function requestGate(string $class): ?string
{
    $file = classFile($class);
    if ($file === null) {
        return null;
    }
    $rc = new ReflectionClass($class);
    if (!$rc->hasMethod('authorize')) {
        return null;
    }
    $m = $rc->getMethod('authorize');
    $body = bodyOf($file, $m->getStartLine(), $m->getEndLine());
    $markers = gateMarkers($body);
    if ($markers) {
        return implode(',', $markers);
    }
    return null;
}

$rows = [];

foreach (Route::getRoutes() as $route) {
    $uri = routeUri($route);
    if (!str_starts_with($uri, 'api/')) {
        continue;
    }
    $methods = array_values(array_intersect(routeMethods($route), MUTATING));
    if (!$methods) {
        continue;
    }

    $gates = [];
    $notes = [];

    foreach (routeMiddleware($route) as $mw) {
        if ($mw === 'role' || $mw === \App\Http\Middleware\RequireRole::class) {
            $gates[] = 'middleware:role';
        }
        if (str_contains($mw, 'can:') || str_contains($mw, 'ability:')) {
            $gates[] = 'middleware:'.$mw;
        }
    }

    $class = controllerClass(routeAction($route));
    $actionMethod = null;
    $action = routeAction($route);
    if (is_string($action) && str_contains($action, '@')) {
        $actionMethod = Str::after($action, '@');
    } elseif (is_array($action) && isset($action[1])) {
        $actionMethod = (string) $action[1];
    }

    if ($class !== null && $actionMethod !== null) {
        $found = resolveMethod($class, $actionMethod);
        foreach ($found as $f) {
            $gates[] = 'controller:'.$f;
        }

        // injected FormRequests on the action signature
        if (classFile($class)) {
            $rc = new ReflectionClass($class);
            if ($rc->hasMethod($actionMethod)) {
                foreach ($rc->getMethod($actionMethod)->getParameters() as $p) {
                    $t = $p->getType();
                    if ($t instanceof ReflectionNamedType && !$t->isBuiltin()) {
                        $g = requestGate($t->getName());
                        if ($g) {
                            $gates[] = 'request:'.$p->getName().' => '.$g;
                        }
                    }
                }
            }
        }
    }

    $rows[] = [
        'method' => implode('|', $methods),
        'uri' => $uri,
        'name' => $route->getName(),
        'controller' => $class ? class_basename($class) : 'closure',
        'action' => $actionMethod,
        'gates' => $gates,
    ];
}

$ungated = array_values(array_filter($rows, fn ($r) => $r['gates'] === []));

echo "TOTAL MUTATING: ".count($rows)."\n";
echo "GATED: ".(count($rows) - count($ungated))."\n";
echo "UNGATED: ".count($ungated)."\n\n";
foreach ($ungated as $r) {
    printf("%-7s %-58s %s@%s\n", $r['method'], '/'.str_replace('api/', '', $r['uri']), $r['controller'], $r['action']);
}
echo "\n\n===== GATED (for convention reference) =====\n";
$g = array_filter($rows, fn ($r) => $r['gates'] !== []);
usort($g, fn ($a, $b) => $a['uri'] <=> $b['uri']);
foreach ($g as $r) {
    printf("%-7s %-50s %s\n", $r['method'], '/'.str_replace('api/', '', $r['uri']), implode(' | ', $r['gates']));
}