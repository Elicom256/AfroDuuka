<?php

namespace Tests\Feature\Payroll;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A salary is set per role, not per worker: everyone holding the role is paid the
 * amount. The table is `salaries` and the role is the unit.
 *
 * What these pin, beyond the obvious CRUD:
 *
 *  - The old contract is gone. `worker_id`, `currency`, `effective_date` and `end_date`
 *    belonged to the per-worker `employee_salaries` table that the role-driven table
 *    replaces, and `EmployeeSalaryController::index()` still sorted by `effective_date`
 *    — a column that does not exist here. Nothing would have failed at compile time.
 *  - The monthly payroll figure must not count yearly or inactive salaries. Adding a
 *    yearly row that inflates "monthly payroll" is the kind of error a UI cannot detect.
 *  - A salary for another business's role, or another branch, must be refused by
 *    validation rather than accepted and silently filtered away on read.
 */
class SalaryTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branch;

    private User $executive;

    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);
        $this->role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'operations',
        ]);

        $this->executive = $this->userWithRole('executive');
    }

    private function userWithRole(string $roleName, ?Business $business = null, ?BusinessBranch $branch = null): User
    {
        $business ??= $this->business;
        $branch ??= $this->branch;

        $role = Role::factory()->create([
            'business_id' => $business->id,
            'name' => $roleName,
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function salary(array $overrides = []): Salary
    {
        $salary = new Salary(array_merge([
            'business_id' => $this->business->id,
            'business_branch_id' => null,
            'role_id' => $this->role->id,
            'amount' => 2_000_000,
            'period' => 'monthly',
            'status' => 'active',
            'set_by' => $this->executive->id,
        ], $overrides));

        $salary->coversAllBranches = ! array_key_exists('business_branch_id', $overrides)
            || $overrides['business_branch_id'] === null;

        $salary->save();

        return $salary;
    }

    public function test_the_salaries_list_loads(): void
    {
        $this->salary(['amount' => 1_500_000]);

        $this->getJson('/api/dashboard/salaries')
            ->assertOk()
            ->assertJsonStructure(['message', 'salaries', 'monthly_payroll', 'active_count'])
            ->assertJsonPath('salaries.data.0.amount', '1500000.00')
            ->assertJsonPath('salaries.data.0.role.name', 'operations');
    }

    public function test_monthly_payroll_counts_only_active_monthly_salaries(): void
    {
        $this->salary(['amount' => 1_000_000, 'period' => 'monthly', 'status' => 'active']);
        $this->salary(['amount' => 12_000_000, 'period' => 'yearly', 'status' => 'active']);
        $this->salary(['amount' => 900_000, 'period' => 'monthly', 'status' => 'inactive']);

        $this->getJson('/api/dashboard/salaries')
            ->assertOk()
            ->assertJsonPath('monthly_payroll', '1000000.00')
            ->assertJsonPath('active_count', 2);
    }

    public function test_a_salary_is_created_for_a_role_and_records_who_set_it(): void
    {
        $response = $this->postJson('/api/dashboard/salaries', [
            'role_id' => $this->role->id,
            'amount' => 2_500_000,
            'period' => 'monthly',
            'status' => 'active',
        ]);

        $response->assertCreated()
            ->assertJsonPath('salary.amount', '2500000.00')
            ->assertJsonPath('salary.role.name', 'operations')
            ->assertJsonPath('salary.set_by.id', $this->executive->id);

        $this->assertDatabaseHas('salaries', [
            'role_id' => $this->role->id,
            'business_id' => $this->business->id,
            'amount' => 2_500_000,
        ]);
    }

    /**
     * business_branch_id is nullable so one salary can cover every branch. That means
     * "all branches" is a real value the API has to accept rather than treat as missing.
     */
    public function test_a_salary_can_cover_every_branch(): void
    {
        $this->postJson('/api/dashboard/salaries', [
            'role_id' => $this->role->id,
            'amount' => 2_500_000,
            'period' => 'monthly',
            'business_branch_id' => null,
        ])->assertCreated();

        $this->assertDatabaseHas('salaries', [
            'role_id' => $this->role->id,
            'business_branch_id' => null,
        ]);
    }

    public function test_a_salary_can_be_pinned_to_one_branch(): void
    {
        $this->postJson('/api/dashboard/salaries', [
            'role_id' => $this->role->id,
            'amount' => 2_500_000,
            'period' => 'monthly',
            'business_branch_id' => $this->branch->id,
        ])->assertCreated();

        $this->assertDatabaseHas('salaries', [
            'role_id' => $this->role->id,
            'business_branch_id' => $this->branch->id,
        ]);
    }

    public function test_a_single_salary_loads_with_its_relations(): void
    {
        $salary = $this->salary();

        $this->getJson("/api/dashboard/salaries/{$salary->id}")
            ->assertOk()
            ->assertJsonPath('salary.role.name', 'operations')
            ->assertJsonPath('salary.set_by.id', $this->executive->id)
            ->assertJsonStructure(['salary' => ['role', 'business_branch', 'set_by', 'employees']]);
    }

    public function test_a_salary_is_updated(): void
    {
        $salary = $this->salary();

        $this->putJson("/api/dashboard/salaries/{$salary->id}", [
            'amount' => 3_000_000,
            'period' => 'yearly',
        ])->assertOk()
            ->assertJsonPath('salary.amount', '3000000.00')
            ->assertJsonPath('salary.period', 'yearly');

        $this->assertDatabaseHas('salaries', [
            'id' => $salary->id,
            'amount' => 3_000_000,
            'period' => 'yearly',
        ]);
    }

    public function test_a_salary_is_soft_deleted(): void
    {
        $salary = $this->salary();

        $this->deleteJson("/api/dashboard/salaries/{$salary->id}")->assertOk();

        $this->assertSoftDeleted('salaries', ['id' => $salary->id]);
        $this->getJson('/api/dashboard/salaries')->assertOk()->assertJsonCount(0, 'salaries.data');
    }

    public function test_a_role_is_required_and_the_period_is_constrained(): void
    {
        $this->postJson('/api/dashboard/salaries', [
            'amount' => 2_500_000,
            'period' => 'monthly',
        ])->assertStatus(422)->assertJsonValidationErrors('role_id');

        $this->postJson('/api/dashboard/salaries', [
            'role_id' => $this->role->id,
            'amount' => 2_500_000,
            'period' => 'fortnightly',
        ])->assertStatus(422)->assertJsonValidationErrors('period');
    }

    /**
     * `exists:roles,id` alone would accept another tenant's role: the salary row would be
     * created, then the branch/business scope would hide it on every read, so the caller
     * would watch a successful-looking request produce nothing.
     */
    public function test_a_salary_cannot_be_created_for_another_business_role(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherRole = Role::factory()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'operations',
        ]);

        $this->postJson('/api/dashboard/salaries', [
            'role_id' => $otherRole->id,
            'amount' => 2_500_000,
            'period' => 'monthly',
        ])->assertStatus(422)->assertJsonValidationErrors('role_id');

        $this->assertDatabaseCount('salaries', 0);
    }

    public function test_a_salary_cannot_be_created_for_another_business_branch(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);

        $this->postJson('/api/dashboard/salaries', [
            'role_id' => $this->role->id,
            'amount' => 2_500_000,
            'period' => 'monthly',
            'business_branch_id' => $otherBranch->id,
        ])->assertStatus(422)->assertJsonValidationErrors('business_branch_id');

        $this->assertDatabaseCount('salaries', 0);
    }

    /**
     * An elevated executive is pinned to one branch by onboarding. validationBranchesFor()
     * is what lets them still set a salary for any branch of their own business — the
     * failure in bugs.md was the reverse: rejected on every branch other than main.
     */
    public function test_an_executive_pinned_to_one_branch_can_still_set_a_salary_for_another(): void
    {
        $second = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $this->postJson('/api/dashboard/salaries', [
            'role_id' => $this->role->id,
            'amount' => 2_500_000,
            'period' => 'monthly',
            'business_branch_id' => $second->id,
        ])->assertCreated();

        $this->assertDatabaseHas('salaries', ['business_branch_id' => $second->id]);
    }

    /**
     * The branch scope is what keeps one branch's payroll out of another's list. The
     * executive here is deliberately not pinned, so it admits every branch of the
     * business — a row from a different business must still be absent.
     */
    public function test_one_business_never_sees_another_business_salaries(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherRole = Role::factory()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'operations',
        ]);

        Salary::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'role_id' => $otherRole->id,
            'amount' => 9_000_000,
            'period' => 'monthly',
            'status' => 'active',
        ]);

        $this->getJson('/api/dashboard/salaries')
            ->assertOk()
            ->assertJsonCount(0, 'salaries.data')
            ->assertJsonPath('monthly_payroll', 0);
    }

    public function test_an_unknown_salary_id_is_a_404(): void
    {
        $this->getJson('/api/dashboard/salaries/999999')->assertNotFound();
    }

    public function test_an_unauthenticated_request_is_refused(): void
    {
        auth()->forgetGuards();

        $this->getJson('/api/dashboard/salaries')->assertUnauthorized();
    }
}
