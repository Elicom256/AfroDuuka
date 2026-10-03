<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Two policy gaps that the earlier audits flagged as false positives or left open.
 *
 * Todos looked covered: TodoPolicy existed and every user in a business shares a
 * business_id, so "same business" was the only test — which means any user could
 * rewrite or close anyone else's task, and TodoController never called the policy at
 * all. The rule is ownership, with managers able to help.
 *
 * Plans looked fine because Plan is scoped globally and a policy file was present,
 * but Plan extends Eloquent\Model rather than BaseModel: there is no business_id and
 * no global scope, so no tenant can contain it. Writes are platform operations.
 */
class AuthorizationPolicyCoverageTest extends TestCase
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

    private function actingAsRole(string $roleName): User
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

        Sanctum::actingAs($user);

        return $user;
    }

    private function todoFor(User $owner): Todo
    {
        return Todo::create([
            'user_id' => $owner->id,
            'business_id' => $this->business->id,
            'title' => 'Count the back shelf',
            'status' => 'undone',
        ]);
    }

    /**
     * plans.mark is a NOT NULL enum, so every seeded row here carries one. Building
     * plans through the model rather than the factory keeps that constraint visible.
     */
    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Bronze',
            'slug' => 'bronze',
            'mark' => 'Affordable',
            'monthly_price' => 10000,
            'yearly_price' => 100000,
            'billing_cycle' => 'monthly',
        ], $overrides));
    }

    public function test_a_user_cannot_update_another_users_todo(): void
    {
        $owner = $this->actingAsRole('Operations');
        $todo = $this->todoFor($owner);

        $other = $this->actingAsRole('Operations');

        $this->actingAs($other);

        $this->patchJson("/api/users/todos/{$todo->id}", ['status' => 'completed'])
            ->assertForbidden();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => 'undone',
        ]);
    }

    public function test_a_user_can_update_their_own_todo(): void
    {
        $user = $this->actingAsRole('Operations');
        $todo = $this->todoFor($user);

        $this->patchJson("/api/users/todos/{$todo->id}", ['status' => 'completed'])
            ->assertOk();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => 'completed',
        ]);
    }

    public function test_a_branch_manager_can_update_another_users_todo(): void
    {
        $owner = $this->actingAsRole('Operations');
        $todo = $this->todoFor($owner);

        $this->actingAsRole('BranchManager');

        $this->patchJson("/api/users/todos/{$todo->id}", ['status' => 'completed'])
            ->assertOk();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => 'completed',
        ]);
    }

    public function test_an_operations_user_cannot_delete_a_todo(): void
    {
        $user = $this->actingAsRole('Operations');
        $todo = $this->todoFor($user);

        $this->deleteJson("/api/users/todos/{$todo->id}")->assertForbidden();

        $this->assertDatabaseHas('todos', ['id' => $todo->id]);
    }

    public function test_an_executive_can_delete_a_todo(): void
    {
        $owner = $this->actingAsRole('Operations');
        $todo = $this->todoFor($owner);

        $this->actingAsRole('Executive');

        $this->deleteJson("/api/users/todos/{$todo->id}")->assertOk();

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_a_todo_from_another_business_is_not_reachable(): void
    {
        $user = $this->actingAsRole('Executive');

        $otherBusiness = Business::factory()->create();
        $foreign = Todo::create([
            'user_id' => $user->id,
            'business_id' => $otherBusiness->id,
            'title' => 'Someone else\'s task',
            'status' => 'undone',
        ]);

        // 404, not 403. The tenant scope on Todo hides the row from the resolver before
        // the policy is ever consulted, so the endpoint does not confirm that the id
        // exists in some other business. That is the stronger of the two answers.
        $this->deleteJson("/api/users/todos/{$foreign->id}")->assertNotFound();

        $this->assertDatabaseHas('todos', ['id' => $foreign->id]);
    }

    public function test_an_executive_cannot_create_a_plan(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/plans', [
            'name' => 'Gold',
            'slug' => 'gold',
            'mark' => 'Best Value',
            'monthly_price' => 50000,
            'yearly_price' => 500000,
            'billing_cycle' => 'monthly',
        ])->assertForbidden();

        $this->assertDatabaseMissing('plans', ['slug' => 'gold']);
    }

    public function test_an_executive_cannot_update_a_plan(): void
    {
        $this->actingAsRole('Executive');

        $plan = $this->plan();

        $this->putJson("/api/plans/{$plan->id}", ['monthly_price' => 1])
            ->assertForbidden();

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'monthly_price' => 10000,
        ]);
    }

    public function test_a_siteadmin_can_create_and_update_a_plan(): void
    {
        $this->actingAsRole('siteadmin');

        $this->postJson('/api/plans', [
            'name' => 'Silver',
            'slug' => 'silver',
            'mark' => 'Most Popular',
            'monthly_price' => 25000,
            'yearly_price' => 250000,
            'billing_cycle' => 'monthly',
        ])->assertCreated();

        $this->assertDatabaseHas('plans', ['slug' => 'silver']);

        $plan = Plan::where('slug', 'silver')->firstOrFail();

        $this->putJson("/api/plans/{$plan->id}", ['monthly_price' => 30000])
            ->assertOk();

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'monthly_price' => 30000,
        ]);
    }

    public function test_a_siteadmin_can_delete_a_plan(): void
    {
        $this->actingAsRole('siteadmin');

        $plan = $this->plan();

        $this->deleteJson("/api/plans/{$plan->id}")->assertOk();

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_an_executive_cannot_delete_a_plan(): void
    {
        $this->actingAsRole('Executive');

        $plan = $this->plan();

        $this->deleteJson("/api/plans/{$plan->id}")->assertForbidden();

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_plan_mark_is_required_because_the_column_has_no_default(): void
    {
        $this->actingAsRole('siteadmin');

        // Guards the reason the rule exists: without mark the insert reaches Postgres
        // with a null and answers 500. A 422 means the contract caught it first.
        $this->postJson('/api/plans', [
            'name' => 'Unmarked',
            'slug' => 'unmarked',
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'billing_cycle' => 'monthly',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mark');

        $this->assertDatabaseMissing('plans', ['slug' => 'unmarked']);
    }

    public function test_the_plan_list_stays_public_but_a_single_plan_needs_a_token(): void
    {
        $plan = $this->plan([
            'name' => 'Trial',
            'slug' => 'trial',
            'monthly_price' => 0,
            'yearly_price' => 0,
        ]);

        // routes/plans.php drops auth:sanctum from the index only, because the signup
        // screen has to show prices before an account exists. The show route keeps the
        // middleware, so this is a deliberate asymmetry rather than an oversight.
        $this->getJson('/api/plans')
            ->assertOk()
            ->assertJsonPath('plans.0.slug', 'trial');

        $this->getJson("/api/plans/{$plan->id}")->assertUnauthorized();

        $this->actingAsRole('Operations');

        $this->getJson("/api/plans/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('plan.slug', 'trial');
    }
}
