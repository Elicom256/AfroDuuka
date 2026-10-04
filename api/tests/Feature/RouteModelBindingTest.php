<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Guards against silently unbound route-model injections.
 *
 * Laravel resolves an implicitly bound model by matching the controller's
 * parameter name against the route's placeholder name, allowing for a
 * snake_case fallback (see ImplicitRouteBinding::getParameterName). When both
 * miss, the binding never happens, the container injects a fresh empty model
 * instead, and the controller runs against a phantom row: `$model->status` is
 * null, every guard reads as false, and the write lands on nothing. Nothing
 * raises, so this only ever surfaces as a missing feature.
 *
 * `Route::apiResource('branch-sales', SaleController::class)` generates
 * `{branch_sale}`, so a controller taking `Sale $sale` received `new Sale` and
 * the completed-sale guard never fired.
 */
class RouteModelBindingTest extends TestCase
{
    public function test_every_api_route_binds_its_model_parameters(): void
    {
        $problems = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $action = $route->getActionName();
            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action);
            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            preg_match_all('/\{([^}?]+)\??\}/', $route->uri(), $matches);
            $routeParameters = $matches[1];

            foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (! $type instanceof ReflectionNamedType) {
                    continue;
                }

                $typeName = $type->getName();
                if ($typeName !== Model::class && ! is_subclass_of($typeName, Model::class)) {
                    continue;
                }

                $checked++;

                if ($this->bindsTo($parameter->getName(), $routeParameters)) {
                    continue;
                }

                $problems[] = sprintf(
                    '  %s %s → %s::%s() expects $%s but the route provides [%s]',
                    implode('|', $route->methods()),
                    $route->uri(),
                    class_basename($class),
                    $method,
                    $parameter->getName(),
                    implode(', ', $routeParameters)
                );
            }
        }

        $this->assertGreaterThan(0, $checked, 'No bound model parameters were found to check');
        $this->assertEmpty(
            $problems,
            "Controller model parameters that the route cannot bind:\n".implode("\n", $problems)
        );
    }

    /**
     * A mismatched name is easy to reintroduce by renaming a controller
     * parameter, so pin the placeholders that were wrong. Each of these routes
     * resolved to an empty model before the placeholder was corrected.
     */
    public function test_resource_placeholders_still_match_their_controller_model(): void
    {
        $uris = [
            'api/sales/branch-sales/{sale}',
            'api/purchases/branch-purchases/{purchase}',
            'api/expenses/branch-expenses/{expense}',
            'api/products/categories/{productCategory}',
            'api/subscriptions/business-subscriptions/{subscription}',
            'api/users/workers/{user}',
            'api/settings/attendance-settings/{attendance_setting}',
            'api/settings/customers-settings/{customers_setting}',
            'api/settings/promotions-settings/{promotions_setting}',
            'api/settings/reports-settings/{reports_setting}',
            'api/settings/suppliers-settings/{suppliers_setting}',
        ];

        $registered = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        foreach ($uris as $uri) {
            $this->assertContains($uri, $registered, "Route {$uri} no longer exists");
        }
    }

    /**
     * Mirrors ImplicitRouteBinding::getParameterName.
     */
    private function bindsTo(string $parameterName, array $routeParameters): bool
    {
        return in_array($parameterName, $routeParameters, true)
            || in_array(Str::snake($parameterName), $routeParameters, true);
    }
}
