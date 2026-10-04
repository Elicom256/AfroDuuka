<?php

namespace Tests\Feature;

use App\Http\Middleware\BlockRestrictedRoleActions;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * BlockRestrictedRoleActions reads the role, and the role is only on the request if
 * something has authenticated it.
 *
 * `auth:sanctum` is route middleware and the deny-rule is api group middleware, so
 * whether the caller is known when the deny-rule runs depends on how the two sort. This
 * drives the middleware directly with no auth in front of it, which is the pessimistic
 * ordering: if the rule only works because something else happened to authenticate
 * first, it breaks here.
 */
class BlockRestrictedRoleActionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create([
            'business_id' => $business->id,
            'name' => $roleName,
        ]);

        return User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);
    }

    /**
     * @param  string|null  $token  null for an unauthenticated caller
     */
    private function runDelete(?string $token): Response
    {
        $request = Request::create('/api/products/1', 'DELETE');

        if ($token !== null) {
            $request->headers->set('Authorization', "Bearer {$token}");
        }

        $middleware = new BlockRestrictedRoleActions;

        return $middleware->handle($request, fn () => new Response('reached controller'));
    }

    public function test_an_operations_token_is_refused_with_no_auth_middleware_in_front(): void
    {
        $user = $this->userWithRole('Operations');

        $response = $this->runDelete($user->createToken('t')->plainTextToken);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringNotContainsString('reached controller', (string) $response->getContent());
    }

    public function test_a_lowercase_operations_token_is_refused(): void
    {
        $user = $this->userWithRole('operations');

        $this->assertSame(403, $this->runDelete($user->createToken('t')->plainTextToken)->getStatusCode());
    }

    public function test_an_executive_token_passes_through(): void
    {
        $user = $this->userWithRole('Executive');

        $response = $this->runDelete($user->createToken('t')->plainTextToken);

        $this->assertStringContainsString('reached controller', (string) $response->getContent());
    }

    public function test_an_unauthenticated_caller_passes_through(): void
    {
        // Not a 403. There is no role to refuse, and auth:sanctum is what answers 401.
        $this->assertStringContainsString('reached controller', (string) $this->runDelete(null)->getContent());
    }

    public function test_a_get_request_is_untouched(): void
    {
        $user = $this->userWithRole('Operations');
        $request = Request::create('/api/products', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken);

        $middleware = new BlockRestrictedRoleActions;
        $response = $middleware->handle($request, fn () => new Response('reached controller'));

        $this->assertStringContainsString('reached controller', (string) $response->getContent());
    }
}
