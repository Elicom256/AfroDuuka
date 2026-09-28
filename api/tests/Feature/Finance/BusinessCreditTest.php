<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessCredit;
use App\Models\Customer;
use App\Models\CustomerCreditTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCreditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessBranch $branch;
    protected Customer $customer;

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
        $this->customer = Customer::factory()->create(['business_id' => $business->id]);

        $this->actingAs($this->user);
    }

    public function test_credit_created_with_open_status(): void
    {
        $response = $this->postJson('/api/finances/business-credits', [
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 75000.00,
            'reference' => 'CRED-001',
            'status' => 'open',
            'description' => 'Customer credit sale',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('business_credits', [
            'customer_id' => $this->customer->id,
            'amount' => 75000.00,
            'status' => 'open',
        ]);
    }

    public function test_credit_balance_reduces_with_payment(): void
    {
        $credit = BusinessCredit::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 60000.00,
            'reference' => 'CRED-002',
            'status' => 'open',
            'description' => 'Balance reduction test',
        ]);

        CustomerCreditTransaction::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'type' => 'payment',
            'amount' => 25000.00,
            'reference' => 'PAY-CRED-001',
        ]);

        $credit->refresh();

        $this->assertEquals(25000.00, $credit->amountPaid());
        $this->assertEquals(35000.00, $credit->balance());
        $this->assertEquals('partial', $credit->lifecycle_status);
    }

    public function test_credit_settles_when_fully_paid(): void
    {
        $credit = BusinessCredit::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 40000.00,
            'reference' => 'CRED-003',
            'status' => 'open',
            'description' => 'Settlement test',
        ]);

        CustomerCreditTransaction::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'type' => 'payment',
            'amount' => 40000.00,
            'reference' => 'PAY-CRED-002',
        ]);

        $credit->refresh();

        $this->assertEquals(0.00, $credit->balance());
        $this->assertEquals('settled', $credit->lifecycle_status);
    }

    public function test_overdue_credit_appears_in_overdue_scope(): void
    {
        $overdue = BusinessCredit::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 55000.00,
            'reference' => 'CRED-004',
            'status' => 'open',
            'description' => 'Overdue credit test',
            'due_date' => now()->subDays(7)->toDateString(),
        ]);

        $current = BusinessCredit::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 55000.00,
            'reference' => 'CRED-005',
            'status' => 'open',
            'description' => 'Not yet due',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->getJson('/api/finances/business-credits/overdue');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($overdue->id));
        $this->assertFalse($ids->contains($current->id));
    }

    public function test_credit_show_endpoint_returns_balance(): void
    {
        $credit = BusinessCredit::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 80000.00,
            'reference' => 'CRED-006',
            'status' => 'open',
            'description' => 'Show endpoint test',
        ]);

        CustomerCreditTransaction::create([
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'type' => 'payment',
            'amount' => 30000.00,
            'reference' => 'PAY-CRED-003',
        ]);

        $response = $this->getJson("/api/finances/business-credits/{$credit->id}");

        $response->assertStatus(200);
        $this->assertEquals(50000.00, $response->json('data.balance'));
        $this->assertEquals(30000.00, $response->json('data.amount_paid'));
        $this->assertEquals('partial', $response->json('data.lifecycle_status'));
    }
}
