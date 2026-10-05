<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Support\Auth\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Role names reach the database as 'Executive', 'BranchManager', 'siteadmin' and worse,
 * and roleName() strips separators before comparing. Anything that compares a raw
 * lowercased name against a literal therefore only works for one spelling of one role.
 *
 * That is how branch managers ended up locked out of the finance area: the gate wanted
 * 'branch_manager' while the role is stored as 'BranchManager'. These tests pin the
 * normalisation so the comparison is spelling-independent.
 */
class RoleNameNormalisationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRoleName(string $name): User
    {
        $business = Business::factory()->create();
        $role = Role::factory()->create([
            'business_id' => $business->id,
            'name' => $name,
        ]);

        return User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => null,
            'role_id' => $role->id,
        ]);
    }

    public static function branchManagerSpellings(): array
    {
        return [
            'camel case' => ['BranchManager'],
            'snake case' => ['branch_manager'],
            'spaced' => ['Branch Manager'],
            'lower' => ['branchmanager'],
            'shouty' => ['BRANCHMANAGER'],
        ];
    }

    #[DataProvider('branchManagerSpellings')]
    public function test_a_branch_manager_is_recognised_however_the_role_is_spelled(string $spelling): void
    {
        $user = $this->userWithRoleName($spelling);

        $this->assertTrue(RolePermissions::isBranchManager($user), "[{$spelling}] was not read as a branch manager.");
        $this->assertTrue(RolePermissions::canManageBranch($user));
        $this->assertTrue(RolePermissions::canManageSensitiveFinance($user));
    }

    public function test_operations_is_not_trusted_with_finance_despite_being_listed(): void
    {
        $user = $this->userWithRoleName('Operations');

        // Membership in SENSITIVE_FINANCE_ROLES is not sufficient on its own.
        $this->assertTrue(RolePermissions::hasAnyRole($user, RolePermissions::SENSITIVE_FINANCE_ROLES));
        $this->assertFalse(RolePermissions::canManageBranch($user));
        $this->assertFalse(RolePermissions::canManageSensitiveFinance($user));
    }

    public function test_the_finance_gate_admits_the_expected_roles(): void
    {
        foreach (['Executive', 'CoreSupport', 'siteadmin', 'BranchManager'] as $name) {
            $this->assertTrue(
                RolePermissions::canManageSensitiveFinance($this->userWithRoleName($name)),
                "[{$name}] should be allowed to manage finance."
            );
        }

        foreach (['Operations', 'editor', 'customer', 'supplier'] as $name) {
            $this->assertFalse(
                RolePermissions::canManageSensitiveFinance($this->userWithRoleName($name)),
                "[{$name}] should not be allowed to manage finance."
            );
        }
    }

    public function test_has_any_role_normalises_both_sides(): void
    {
        $user = $this->userWithRoleName('BranchManager');

        $this->assertTrue(RolePermissions::hasAnyRole($user, ['branch_manager']));
        $this->assertTrue(RolePermissions::hasAnyRole($user, ['Branch Manager']));
        $this->assertFalse(RolePermissions::hasAnyRole($user, ['operations']));
    }
}
