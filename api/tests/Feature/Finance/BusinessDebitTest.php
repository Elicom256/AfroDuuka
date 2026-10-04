<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessDebit;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDebitTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessBranch $branch;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id]);
        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $this->supplier = Supplier::factory()->create(['business_id' => $business->id]);

        $this->actingAs($this->user);
    }

    /**
     * RoleFactory's default name is 'Operations', so $this->user is an Operations
     * account by accident rather than by decision. That accident used to be invisible
     * because nothing on this controller asked. It matters now, because the two halves
     * of this resource have deliberately different answers:
     *
     *   - pay() is ungated. Settling a debt records money already committed, which is
     *     a floor task. The pay tests below therefore keep the Operations user on
     *     purpose — they are the evidence that this stays open.
     *   - store()/update() are gated to canManageBranch(). Asserting that the business
     *     owes a supplier money is a ledger decision, not a till one.
     *
     * The refusal side of that split is pinned in MutatingEndpointAuthorizationTest.
     */
    private function actingAsRole(string $roleName): void
    {
        $role = Role::factory()->create([
            'business_id' => $this->user->business_id,
            'name' => $roleName,
        ]);

        $this->user->forceFill(['role_id' => $role->id])->save();

        $this->actingAs($this->user->fresh());
    }

    public function test_debit_created_with_open_status_and_full_balance(): void
    {
        $this->actingAsRole('Executive');

        $response = $this->postJson('/api/finances/business-debits', [
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 50000.00,
            'reference' => 'INV-001',
            'status' => 'open',
            'description' => 'Supplier goods on credit',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('business_debits', [
            'supplier_id' => $this->supplier->id,
            'amount' => 50000.00,
            'status' => 'open',
        ]);
    }

    public function test_partial_payment_reduces_balance(): void
    {
        $debit = BusinessDebit::create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 50000.00,
            'reference' => 'INV-002',
            'status' => 'open',
            'description' => 'Partial payment test',
        ]);

        $response = $this->postJson("/api/finances/business-debits/{$debit->id}/pay", [
            'amount' => 20000.00,
            'reference' => 'PAY-001',
        ]);

        $response->assertStatus(201);

        $debit->refresh();
        $this->assertEquals(20000.00, $debit->amountPaid());
        $this->assertEquals(30000.00, $debit->balance());
        $this->assertEquals('partial', $debit->lifecycle_status);
    }

    public function test_full_payment_settles_debt(): void
    {
        $debit = BusinessDebit::create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 30000.00,
            'reference' => 'INV-003',
            'status' => 'open',
            'description' => 'Full settlement test',
        ]);

        $response = $this->postJson("/api/finances/business-debits/{$debit->id}/pay", [
            'amount' => 30000.00,
        ]);

        $response->assertStatus(201);

        $debit->refresh();
        $this->assertEquals(0.00, $debit->balance());
        $this->assertEquals('settled', $debit->lifecycle_status);
        $this->assertEquals('settled', $debit->status);
    }

    public function test_overpayment_is_rejected(): void
    {
        $debit = BusinessDebit::create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 10000.00,
            'reference' => 'INV-004',
            'status' => 'open',
            'description' => 'Overpayment test',
        ]);

        $response = $this->postJson("/api/finances/business-debits/{$debit->id}/pay", [
            'amount' => 15000.00,
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseMissing('business_debit_payments', [
            'business_debit_id' => $debit->id,
            'amount' => 15000.00,
        ]);
    }

    public function test_overdue_debt_appears_in_overdue_scope(): void
    {
        $overdue = BusinessDebit::create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 25000.00,
            'reference' => 'INV-005',
            'status' => 'open',
            'description' => 'Overdue test',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $current = BusinessDebit::create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 25000.00,
            'reference' => 'INV-006',
            'status' => 'open',
            'description' => 'Not yet due',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->getJson('/api/finances/business-debits/overdue');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($overdue->id));
        $this->assertFalse($ids->contains($current->id));
    }

    public function test_debit_summary_endpoint_returns_balance(): void
    {
        $debit = BusinessDebit::create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 40000.00,
            'reference' => 'INV-007',
            'status' => 'open',
            'description' => 'Summary test',
        ]);

        $this->postJson("/api/finances/business-debits/{$debit->id}/pay", [
            'amount' => 10000.00,
        ]);

        $response = $this->getJson("/api/finances/business-debits/{$debit->id}");

        $response->assertStatus(200);
        $this->assertEquals(30000.00, $response->json('data.balance'));
        $this->assertEquals(10000.00, $response->json('data.amount_paid'));
        $this->assertEquals('partial', $response->json('data.lifecycle_status'));
    }
}
