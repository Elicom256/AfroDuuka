<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockRestrictedRoleActions;
use App\Http\Middleware\RequireRole;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Printer;
use App\Models\Role;
use App\Models\User;
use App\Support\Auth\RolePermissions;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * A mechanical audit of the mutating API surface, so item 5 cannot silently rot.
 *
 * MutatingEndpointAuthorizationTest proves that specific endpoints behave correctly.
 * It cannot notice the endpoint that was added next week with no gate at all, which is
 * how every hole in this list got here in the first place. This test therefore ignores
 * the endpoints it already knows about and only asks the structural question: does this
 * route resolve to an authorization decision anywhere?
 *
 * What "resolves" means is deliberately coarse. A route counts as gated when the route
 * or controller middleware names a role or ability, when the controller action or a
 * helper it delegates to reaches RolePermissions/authorize()/Gate::, or when a
 * FormRequest injected into the action has a real authorize(). That is a lower bound on
 * authorization, not proof of it — a method can contain all of those markers and still
 * get the condition backwards. Correctness is the behavioural suite's job; this one's
 * job is to make total absence impossible to merge unnoticed.
 *
 * Every route it cannot gate has to appear in INTENTIONAL below with the reason it is
 * safe. The check runs both ways, so a route gaining a gate without its entry being
 * removed fails here too — that is deliberate, because deleting an exception should be a
 * conscious edit to the justification rather than a side effect of someone tidying up.
 */
class MutatingRouteAuthorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    /** Verbs that write. GET/HEAD are out of scope for an authorization audit. */
    private const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Mutating routes with no role gate of their own, and why that is correct.
     *
     * The DELETE entries are all answered by BlockRestrictedRoleActions, which is
     * appended to the whole api group; test_the_delete_exceptions_rely_on_a_gate_that_is_actually
     * _registered() pins that registration rather than taking this comment's word for it.
     */
    private const INTENTIONAL = [
        'POST api/webhooks/ses' => 'Inbound SNS notification, authenticated by signature in the controller rather than by a role.',
        'POST api/users/login' => 'Pre-authentication. There is no role to check yet.',
        'POST api/users/signup' => 'Pre-authentication. The role is assigned by this request, not checked against it.',
        'POST api/users/logout' => 'Ends a session. It cannot touch another user\'s session, and auth:sanctum is in front of it.',
        'PATCH api/users/update' => 'Scoped to the caller\'s own profile by the tenant scope on User.',
        'POST api/users/todos' => 'A personal list scoped to the caller by the tenant scope on Todo.',

        'DELETE api/users/notifications/{notification}' => 'Central delete gate; the row is also scoped to the caller.',
        'POST api/users/notifications/{notification}/read' => 'Reads a caller-owned row to flip one boolean.',
        'POST api/users/notifications/mark-all-read' => 'Bulk version of the previous route, same caller-owned rows.',
        'POST api/users/notifications/clear-all' => 'Deletes the caller\'s own notifications and nothing else.',

        'POST api/sales/branch-sales' => 'Selling at the till is the Operations role\'s actual job; role middleware would lock the counter.',
        'PATCH|PUT api/sales/branch-sales/{sale}' => 'Same floor argument. Sales are additionally blocked from being voided, not from being recorded.',

        'POST api/finances/business-debits/{businessDebit}/pay' => 'Records a payment that has already left the till. Opening a debt is gated; settling one is not.',

        'DELETE api/finances/business-debits/{business_debit}' => 'Central delete gate.',
        'DELETE api/finances/business-credits/{business_credit}' => 'Central delete gate.',
        'DELETE api/currency-rates/{currencyRate}' => 'Central delete gate.',
        'DELETE api/payment-gateways/{paymentGateway}' => 'Central delete gate.',
        'DELETE api/printers/{printer}' => 'Central delete gate.',
        'DELETE api/reorder-rules/{reorderRule}' => 'Central delete gate.',
        'DELETE api/report-exports/{reportExport}' => 'Central delete gate.',
        'DELETE api/sale-orders/{sale_order}' => 'Central delete gate.',
        'DELETE api/pos/sales/held/{id}' => 'Central delete gate; the held ticket is the caller\'s own till.',

        'POST api/pos/cart/validate' => 'Read-only despite the verb; prices a cart and writes nothing.',
        'POST api/pos/checkout' => 'Completing the sale Operations is there to take.',
        'POST api/pos/sales/hold' => 'Parks the caller\'s own till ticket.',
    ];

    // ------------------------------------------------------------------ structure

    public function test_every_mutating_api_route_resolves_to_a_gate_or_a_documented_exception(): void
    {
        $ungated = [];

        foreach ($this->mutatingApiRoutes() as $route) {
            if ($this->resolveGates($route) === []) {
                $ungated[$this->key($route)] = $this->describe($route);
            }
        }

        $undocumented = array_diff_key($ungated, self::INTENTIONAL);

        $this->assertSame([], $undocumented, sprintf(
            "%d mutating API route(s) have no resolvable authorization gate.\nEach needs a gate, or an entry in %s::INTENTIONAL explaining why it is safe:\n%s",
            count($undocumented),
            self::class,
            implode("\n", $undocumented)
        ));

        // The reverse direction. A gate landing on an exception route means the
        // justification above is now wrong, and a stale exception is how an audit
        // becomes a list of things nobody rereads.
        $stale = array_diff_key(self::INTENTIONAL, $ungated);

        $this->assertSame([], $stale, sprintf(
            "%d route(s) are listed in %s::INTENTIONAL but are now gated.\nDelete the entry and move the reason onto the gate that replaced it:\n%s",
            count($stale),
            self::class,
            implode("\n", $stale)
        ));
    }

    /**
     * Pins the claim the DELETE half of INTENTIONAL rests on. The comment above says
     * every DELETE is answered by BlockRestrictedRoleActions, so if that middleware is
     * ever dropped from the api group the exceptions stop being safe and the whole list
     * has to be re-examined — which is exactly what should not be discovered in
     * production.
     */
    public function test_the_delete_exceptions_rely_on_a_gate_that_is_actually_registered(): void
    {
        $groups = app(Kernel::class)->getMiddlewareGroups();

        $this->assertContains(
            BlockRestrictedRoleActions::class,
            $groups['api'] ?? [],
            'BlockRestrictedRoleActions is not appended to the api group, so the DELETE '
            .'routes listed in INTENTIONAL are ungated rather than centrally gated.'
        );

        $documented = 0;

        foreach (array_keys(self::INTENTIONAL) as $entry) {
            if (Str::startsWith($entry, 'DELETE ')) {
                $documented++;
            }
        }

        $this->assertGreaterThan(0, $documented, 'No DELETE exception is documented, so the assertion above proves nothing on its own.');
    }

    /**
     * The audit is structural, so this is the behavioural counterpart for one of the
     * documented DELETE exceptions: a real route, a real row, and the 403 that
     * BlockRestrictedRoleActions produces for a role with no delete authority.
     *
     * If this fails, the central gate is not running ahead of the controller and the
     * DELETE entries in INTENTIONAL are describing a control that does not exist.
     */
    public function test_a_documented_delete_exception_is_refused_for_operations(): void
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        // RoleFactory's default is Operations, which is the role with no delete
        // authority — exactly what this route has to refuse.
        $user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => Role::factory()->create([
                'business_id' => $business->id,
                'name' => 'Operations',
            ])->id,
        ]);

        $printer = Printer::create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'name' => 'Front counter',
            'type' => 'usb',
        ]);

        Sanctum::actingAs($user);

        $this->assertFalse(
            RolePermissions::canDelete($user),
            'This test needs a role with no delete authority; the seeded role has one.'
        );

        $this->deleteJson("/api/printers/{$printer->id}")->assertForbidden();

        $this->assertDatabaseHas('printers', ['id' => $printer->id]);
    }

    // ------------------------------------------------------------------- resolver

    /**
     * Every mutating route under api/, keyed by nothing — the list is rebuilt on every
     * run so a newly registered route shows up without touching this file.
     *
     * @return array<int, Route>
     */
    private function mutatingApiRoutes(): array
    {
        $routes = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! Str::startsWith($route->uri(), 'api/')) {
                continue;
            }

            if (array_intersect($route->methods(), self::MUTATING) !== []) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    /**
     * Every authorization decision reachable from a route, as human-readable strings.
     *
     * Empty means the audit has found nothing, which is the only thing the callers care
     * about — the strings exist so a failure message can say *why* a route was believed
     * gated while it was being triaged.
     *
     * @return array<int, string>
     */
    private function resolveGates(Route $route): array
    {
        $gates = $this->routeMiddlewareGates($route);

        [$controller, $action] = $this->controllerAndAction($route);

        if ($controller === null || $action === null) {
            return $gates;
        }

        foreach ($this->resolveMethodGates($controller, $action) as $marker) {
            $gates[] = 'controller: '.$marker;
        }

        foreach ($this->injectedRequestGates($controller, $action) as $marker) {
            $gates[] = 'request: '.$marker;
        }

        return $gates;
    }

    /** @return array<int, string> */
    private function routeMiddlewareGates(Route $route): array
    {
        $gates = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if ($middleware === 'role' || $middleware === RequireRole::class) {
                $gates[] = 'middleware: role';
            }

            if (Str::contains($middleware, ['can:', 'ability:'])) {
                $gates[] = 'middleware: '.$middleware;
            }
        }

        return $gates;
    }

    /**
     * Gate markers in one method, following $this->helper() delegation.
     *
     * Several controllers keep their authorization in a private method rather than the
     * action, so stopping at the action would report real gates as missing.
     *
     * @param  array<int, string>  $visited
     * @return array<int, string>
     */
    private function resolveMethodGates(string $controller, string $method, array $visited = []): array
    {
        if (in_array($method, $visited, true)) {
            return [];
        }

        $body = $this->methodBody($controller, $method);

        if ($body === null) {
            return [];
        }

        $markers = $this->gateMarkers($body);

        if ($markers !== []) {
            return $markers;
        }

        $visited[] = $method;

        foreach ($this->calledMethods($body) as $helper) {
            $found = $this->resolveMethodGates($controller, $helper, $visited);

            if ($found !== []) {
                return ['helper '.$helper.'() → '.implode(', ', $found)];
            }
        }

        return [];
    }

    /** @return array<int, string> */
    private function injectedRequestGates(string $controller, string $action): array
    {
        $gates = [];

        foreach ($this->methodParameters($controller, $action) as $type) {
            if (! $this->isFormRequest($type)) {
                continue;
            }

            $authorize = $this->methodBody($type, 'authorize');

            if ($authorize === null) {
                continue;
            }

            $markers = $this->gateMarkers($authorize);

            foreach ($markers as $marker) {
                $gates[] = class_basename($type).'::authorize → '.$marker;
            }
        }

        return $gates;
    }

    /**
     * The shapes this codebase authorizes with. Anything not listed here is invisible to
     * the audit and will be reported as ungated, which is the safe direction to fail.
     *
     * `Auth::check()` and an unconditional `return true` are deliberately absent. Both
     * answer "is somebody signed in", not "may this role do this", and treating either
     * as a gate is precisely the confusion item 5 was raised about — a request that
     * validates its input and then authorizes everyone reaches every role equally.
     *
     * @return array<int, string>
     */
    private function gateMarkers(string $body): array
    {
        $markers = [];

        if (Str::contains($body, 'RolePermissions::')) {
            $markers[] = 'RolePermissions';
        }

        if (Str::contains($body, '$this->authorize(')) {
            $markers[] = '$this->authorize()';
        }

        if (Str::contains($body, 'Gate::')) {
            $markers[] = 'Gate::';
        }

        if (Str::contains($body, 'Policies\\')) {
            $markers[] = 'policy reference';
        }

        // Hand-rolled role checks, e.g. FinanceController's in-array($role, [...]).
        if (Str::contains($body, ['abort_unless(', 'abort_if(', 'abort(']) && Str::contains($body, 'role')) {
            $markers[] = 'abort() + role';
        }

        return $markers;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function controllerAndAction(Route $route): array
    {
        $action = $route->getAction('uses');

        if (is_string($action) && Str::contains($action, '@')) {
            return [Str::before($action, '@'), Str::after($action, '@')];
        }

        if (is_array($action) && isset($action[0], $action[1])) {
            $controller = is_object($action[0]) ? $action[0]::class : (string) $action[0];

            return [$controller, (string) $action[1]];
        }

        // A closure route has no class to reflect on, so it reads as ungated.
        return [null, null];
    }

    private function methodBody(string $class, string $method): ?string
    {
        $file = $this->classFile($class);

        if ($file === null || ! method_exists($class, $method)) {
            return null;
        }

        $reflected = new ReflectionMethod($class, $method);
        $lines = file($file);

        if ($lines === false) {
            return null;
        }

        return implode('', array_slice(
            $lines,
            $reflected->getStartLine() - 1,
            $reflected->getEndLine() - $reflected->getStartLine() + 1
        ));
    }

    /** @return array<int, string> */
    private function methodParameters(string $class, string $method): array
    {
        if (! method_exists($class, $method)) {
            return [];
        }

        $types = [];

        foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $types[] = $type->getName();
            }
        }

        return $types;
    }

    /** @return array<int, string> */
    private function calledMethods(string $body): array
    {
        preg_match_all('/\$this->(\w+)\s*\(/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    private function classFile(string $class): ?string
    {
        if (! class_exists($class)) {
            return null;
        }

        return (new ReflectionClass($class))->getFileName() ?: null;
    }

    private function isFormRequest(string $class): bool
    {
        return is_subclass_of($class, FormRequest::class);
    }

    /** Stable identity for a route: sorted verbs plus the full uri, parameters included. */
    private function key(Route $route): string
    {
        $methods = array_values(array_intersect($route->methods(), self::MUTATING));
        sort($methods);

        return implode('|', $methods).' '.$route->uri();
    }

    /** Points at the code to look at when a route shows up as ungated. */
    private function describe(Route $route): string
    {
        [$controller, $action] = $this->controllerAndAction($route);

        return $controller === null
            ? $this->key($route).' — closure route, no controller to inspect'
            : $this->key($route).' — '.Str::afterLast($controller, '\\').'@'.$action;
    }
}
