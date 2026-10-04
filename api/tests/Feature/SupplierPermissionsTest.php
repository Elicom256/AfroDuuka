<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\SuppliersSettings;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A supplier is who the business buys from, so authoring one is Executive work.
 *
 * SupplierService::createSupplier never stamps business_branch_id, so the row lands
 * branch-less and EffectiveBranchScope keeps it visible to every branch of the
 * business. That is the whole reason the split is read/write rather than all/nothing:
 * a BranchManager's purchases have to render a supplier name, so they must be able to
 * read the list, but there is one supplier_code per business and a branch manager must
 * not fork it per branch.
 *
 * These assert the API rather than the UI. Hiding the button is not the control — the
 * endpoint is.
 */
class SupplierPermissionsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Business $business;

    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // StoreSupplierRequest and SupplierService both look the counterparty's role up by
        // name, and update requires it to resolve to something.
        Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'supplier',
        ]);

        SuppliersSettings::create([
            'business_id' => $this->business->id,
            'status' => 'enabled',
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

    /**
     * A business-level supplier, as SupplierService leaves it: business_branch_id null.
     */
    private function supplier(array $overrides = []): Supplier
    {
        return Supplier::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => null,
            'company_name' => 'Acme Supplies Ltd',
            'status' => 'active',
        ] + $overrides);
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => 'jane@acme.test',
            // Required by the users table even though StoreSupplierRequest calls phone
            // nullable, and validated as exactly ten digits.
            'phone' => '0700123456',
            'company_name' => 'Acme Supplies Ltd',
        ], $overrides);
    }

    public function test_a_branch_manager_can_read_the_supplier_list(): void
    {
        $this->actingAsRole('BranchManager');
        $this->supplier();

        $this->getJson('/api/dashboard/suppliers')
            ->assertStatus(200)
            ->assertJsonPath('suppliers.0.company_name', 'Acme Supplies Ltd');
    }

    public function test_a_branch_manager_can_read_a_supplier(): void
    {
        $this->actingAsRole('BranchManager');
        $supplier = $this->supplier();

        $this->getJson("/api/dashboard/suppliers/{$supplier->id}")
            ->assertStatus(200)
            ->assertJsonPath('supplier.company_name', 'Acme Supplies Ltd');
    }

    public function test_a_branch_manager_cannot_create_a_supplier(): void
    {
        $this->actingAsRole('BranchManager');

        $this->postJson('/api/dashboard/suppliers', $this->storePayload())
            ->assertStatus(403);

        $this->assertDatabaseMissing('suppliers', ['company_name' => 'Acme Supplies Ltd']);
        $this->assertDatabaseMissing('users', ['email' => 'jane@acme.test']);
    }

    public function test_a_branch_manager_cannot_update_a_supplier(): void
    {
        $this->actingAsRole('BranchManager');
        $supplier = $this->supplier();

        $this->putJson("/api/dashboard/suppliers/{$supplier->id}", [
            'company_name' => 'Hijacked Supplies',
            'firstname' => 'Mallory',
            'status' => 'active',
        ])->assertStatus(403);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'company_name' => 'Acme Supplies Ltd',
        ]);
    }

    public function test_a_branch_manager_cannot_delete_a_supplier(): void
    {
        $this->actingAsRole('BranchManager');
        $supplier = $this->supplier();

        $this->deleteJson("/api/dashboard/suppliers/{$supplier->id}")->assertStatus(403);

        // Purchases reference this supplier, so a delete here would take the
        // procurement history with it.
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_a_readable_supplier_still_refuses_the_write(): void
    {
        $this->actingAsRole('BranchManager');
        $supplier = $this->supplier();

        // Readable, therefore in scope, therefore a write the naive policy allowed.
        $this->getJson("/api/dashboard/suppliers/{$supplier->id}")->assertStatus(200);

        $this->deleteJson("/api/dashboard/suppliers/{$supplier->id}")->assertStatus(403);
    }

    public function test_an_executive_can_still_create_a_supplier(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/dashboard/suppliers', $this->storePayload())->assertStatus(200);

        $this->assertDatabaseHas('suppliers', ['company_name' => 'Acme Supplies Ltd']);
    }

    public function test_an_executive_can_still_update_a_supplier(): void
    {
        $this->actingAsRole('Executive');
        $supplier = $this->supplier();

        $this->putJson("/api/dashboard/suppliers/{$supplier->id}", [
            'company_name' => 'Renamed Supplies',
            'firstname' => 'Jane',
            'status' => 'active',
        ])->assertStatus(200);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'company_name' => 'Renamed Supplies',
        ]);
    }

    public function test_an_executive_can_still_delete_a_supplier(): void
    {
        $this->actingAsRole('Executive');
        $supplier = $this->supplier();

        $this->deleteJson("/api/dashboard/suppliers/{$supplier->id}")->assertStatus(200);

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_the_role_name_is_matched_case_insensitively(): void
    {
        $this->actingAsRole('branch_manager');
        $supplier = $this->supplier();

        $this->deleteJson("/api/dashboard/suppliers/{$supplier->id}")->assertStatus(403);
        $this->postJson('/api/dashboard/suppliers', $this->storePayload())->assertStatus(403);
    }

    /**
     * The cases above all use Sanctum::actingAs(), which resolves the user before any
     * middleware runs. This signs in for real — a bearer token through the actual stack —
     * so a BranchManager's write is refused by RequireRole plus the policy together
     * rather than by a pre-resolved user.
     */
    public function test_a_bearer_token_write_is_refused_end_to_end(): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'BranchManager',
        ]);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $supplier = $this->supplier();

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/dashboard/suppliers')
            ->assertStatus(200);

        $this->withToken($token)
            ->deleteJson("/api/dashboard/suppliers/{$supplier->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
