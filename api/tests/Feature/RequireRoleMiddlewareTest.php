<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The RequireRole middleware gates executive-level route groups (expenses, settings,
 * audits, reports, tax, procurement, the /dashboard API, user management).
 *
 * Every test here uses a real bearer token rather than Sanctum::actingAs(): actingAs
 * resolves the user before the middleware pipeline runs, so a middleware test built
 * on it would pass even if the middleware were removed.
 */
class RequireRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
    }

    private function bearerTokenFor(string $roleName): string
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => $roleName,
        ]);

        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        return $user->createToken('auth_token')->plainTextToken;
    }

    public function test_operations_is_refused_expenses(): void
    {
        $this->withToken($this->bearerTokenFor('Operations'))
            ->getJson('/api/expenses/branch-expenses')
            ->assertStatus(403);
    }

    public function test_operations_is_refused_settings(): void
    {
        $this->withToken($this->bearerTokenFor('Operations'))
            ->getJson('/api/settings/attendance-settings')
            ->assertStatus(403);
    }

    public function test_operations_is_refused_the_user_index(): void
    {
        $this->withToken($this->bearerTokenFor('Operations'))
            ->getJson('/api/users')
            ->assertStatus(403);
    }

    public function test_operations_cannot_delete_another_user(): void
    {
        $token = $this->bearerTokenFor('Operations');
        $ownerRole = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Executive',
        ]);
        $owner = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $ownerRole->id,
        ]);

        $this->withToken($token)
            ->deleteJson("/api/users/workers/{$owner->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_tenant_cannot_enumerate_or_ban_businesses(): void
    {
        $token = $this->bearerTokenFor('Executive');
        $otherBusiness = Business::factory()->create();

        $this->withToken($token)
            ->getJson('/api/super-admin/businesses')
            ->assertStatus(403);

        $this->withToken($token)
            ->patchJson("/api/super-admin/businesses/{$otherBusiness->id}/status", [
                'status' => 'banned',
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('businesses', [
            'id' => $otherBusiness->id,
            'status' => 'active',
        ]);
    }

    public function test_site_admin_can_enumerate_businesses(): void
    {
        $this->withToken($this->bearerTokenFor('siteadmin'))
            ->getJson('/api/super-admin/businesses')
            ->assertOk();
    }

    public function test_operations_is_refused_the_executive_dashboard_api(): void
    {
        $this->withToken($this->bearerTokenFor('Operations'))
            ->getJson('/api/dashboard/business')
            ->assertStatus(403);
    }

    public function test_operations_is_refused_tax_management(): void
    {
        $this->withToken($this->bearerTokenFor('Operations'))
            ->getJson('/api/tax-categories')
            ->assertStatus(403);
    }

    public function test_operations_is_refused_data_exports(): void
    {
        $this->withToken($this->bearerTokenFor('Operations'))
            ->getJson('/api/exports/products')
            ->assertStatus(403);
    }

    public function test_operations_can_still_reach_open_routes(): void
    {
        $token = $this->bearerTokenFor('Operations');

        // The floor workflow must not be caught by the gate: selling, the catalogue,
        // purchase orders (operations creates them), reports (branch-scoped), and
        // product losses (operations records them from stock counts).
        $this->withToken($token)->getJson('/api/products')->assertStatus(200);
        $this->withToken($token)->getJson('/api/sales/branch-sales')->assertStatus(200);
        $this->withToken($token)->getJson('/api/users/me')->assertStatus(200);
        $this->withToken($token)->getJson('/api/users/workers')->assertStatus(200);
        $this->withToken($token)->getJson('/api/purchase-orders')->assertStatus(200);
        $this->withToken($token)->getJson('/api/procurement/procurement')->assertStatus(200);
        $this->withToken($token)->getJson('/api/reports/monthly-performance')->assertStatus(200);
        $this->withToken($token)->getJson('/api/product-losses/summary')->assertStatus(200);
    }

    public function test_executive_can_reach_gated_routes(): void
    {
        $token = $this->bearerTokenFor('Executive');

        $this->withToken($token)->getJson('/api/expenses/branch-expenses')->assertStatus(200);
        $this->withToken($token)->getJson('/api/settings/attendance-settings')->assertStatus(200);
        $this->withToken($token)->getJson('/api/users')->assertStatus(200);
        $this->withToken($token)->getJson('/api/tax-categories')->assertStatus(200);
        $this->withToken($token)->getJson('/api/exports/products')->assertStatus(200);
    }

    public function test_branch_manager_can_reach_gated_routes(): void
    {
        // BranchManager holds near-executive powers inside its own branch scope.
        $token = $this->bearerTokenFor('BranchManager');

        $this->withToken($token)->getJson('/api/expenses/branch-expenses')->assertStatus(200);
        $this->withToken($token)->getJson('/api/tax-categories')->assertStatus(200);
    }

    public function test_branch_manager_cannot_delete_business_wide_roles(): void
    {
        $token = $this->bearerTokenFor('BranchManager');
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Operations',
        ]);

        $this->withToken($token)
            ->deleteJson("/api/dashboard/roles/{$role->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_an_unauthenticated_request_is_refused_by_auth_before_the_role_gate(): void
    {
        $this->getJson('/api/expenses/branch-expenses')->assertStatus(401);
    }

    public function test_branch_manager_sees_only_its_own_branch_activity(): void
    {
        $otherBranch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);

        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'In my branch',
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
        ]);
        ActivityLog::create([
            'log_name' => 'default',
            'description' => 'In another branch',
            'business_id' => $this->business->id,
            'business_branch_id' => $otherBranch->id,
        ]);

        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'BranchManager',
        ]);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/dashboard/activity-logs');

        $response->assertStatus(200);
        $descriptions = array_column($response->json('data'), 'description');
        $this->assertContains('In my branch', $descriptions);
        $this->assertNotContains('In another branch', $descriptions);
    }
}
