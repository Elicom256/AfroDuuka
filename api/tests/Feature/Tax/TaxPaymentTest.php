<?php

namespace Tests\Feature\Tax;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\TaxCategory;
use App\Models\TaxPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaxPaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Business $business;

    protected BusinessBranch $branch;

    protected BusinessBranch $otherBranch;

    protected TaxCategory $category;

    protected TaxCategory $otherCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->otherBranch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        // Named explicitly: RoleFactory defaults to 'Operations', which holds no delete
        // authority, and the delete case here is about the payment's own rules — not
        // about who may delete. See OperationsRolePermissionsTest for the role boundary.
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Executive',
        ]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->category = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);
        $this->otherCategory = TaxCategory::factory()->create(['business_branch_id' => $this->otherBranch->id]);

        Sanctum::actingAs($this->user);
    }

    public function test_can_record_payment(): void
    {
        $response = $this->postJson('/api/tax-payments', [
            'tax_category_id' => $this->category->id,
            'amount' => 2_000_000,
            'payment_date' => '2026-08-15',
            'tax_period_start' => '2026-08-01',
            'tax_period_end' => '2026-08-31',
            'reference' => 'REF-1234',
            'notes' => 'August VAT remittance',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('payment.amount', 2000000)
            ->assertJsonPath('payment.tax_category_id', $this->category->id)
            ->assertJsonPath('payment.reference', 'REF-1234');

        $this->assertDatabaseHas('tax_payments', [
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'amount' => 2_000_000,
        ]);
    }

    public function test_can_list_payments(): void
    {
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
        ]);
        TaxPayment::factory()->create([
            'business_branch_id' => $this->otherBranch->id,
            'tax_category_id' => $this->otherCategory->id,
        ]);

        $response = $this->getJson('/api/tax-payments');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'payments' => [['id', 'amount', 'payment_date', 'tax_category']]]);
        $this->assertCount(1, $response->json('payments'));
    }

    public function test_can_update_payment(): void
    {
        $payment = TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
        ]);

        $this->putJson("/api/tax-payments/{$payment->id}", [
            'amount' => 3_500_000,
            'reference' => 'REF-UPDATED',
        ])
            ->assertStatus(200)
            ->assertJsonPath('payment.amount', 3500000)
            ->assertJsonPath('payment.reference', 'REF-UPDATED');
    }

    public function test_payment_amount_must_be_positive(): void
    {
        $this->postJson('/api/tax-payments', [
            'tax_category_id' => $this->category->id,
            'amount' => 0,
            'payment_date' => '2026-08-15',
        ])->assertStatus(422);

        $this->postJson('/api/tax-payments', [
            'tax_category_id' => $this->category->id,
            'amount' => -500,
            'payment_date' => '2026-08-15',
        ])->assertStatus(422);
    }

    public function test_payment_tax_category_must_belong_to_own_branch(): void
    {
        // BranchManager is not elevated — cross-branch tax category must be rejected.
        // Executive is elevated and may use any branch in their business; see
        // EffectiveBranchScope::validationBranchesFor().
        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'BranchManager']);
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/tax-payments', [
            'tax_category_id' => $this->otherCategory->id,
            'amount' => 1_000_000,
            'payment_date' => '2026-08-15',
        ])->assertStatus(422);
    }

    public function test_can_filter_by_date_range(): void
    {
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'payment_date' => '2026-01-10',
            'amount' => 100_000,
        ]);
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'payment_date' => '2026-06-20',
            'amount' => 200_000,
        ]);

        $response = $this->getJson('/api/tax-payments?from=2026-03-01&to=2026-12-31');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payments'));
        $this->assertEquals(200_000, (float) $response->json('payments.0.amount'));
    }

    public function test_can_filter_by_tax_category(): void
    {
        $otherOwnCategory = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id]);

        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'amount' => 100_000,
        ]);
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $otherOwnCategory->id,
            'amount' => 200_000,
        ]);

        $response = $this->getJson('/api/tax-payments?tax_category_id='.$this->category->id);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payments'));
    }

    public function test_analytics_aggregates_total_payments(): void
    {
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1_000_000,
        ]);
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500_000,
        ]);
        TaxPayment::factory()->create([
            'business_branch_id' => $this->otherBranch->id,
            'tax_category_id' => $this->otherCategory->id,
            'payment_date' => now()->toDateString(),
            'amount' => 9_000_000,
        ]);

        $response = $this->getJson('/api/tax-payments/analytics');

        $response->assertStatus(200)
            ->assertJsonPath('analytics.total_paid', 1_500_000)
            ->assertJsonPath('analytics.total_count', 2);
    }

    public function test_analytics_aggregates_payments_by_category(): void
    {
        $paye = TaxCategory::factory()->create(['business_branch_id' => $this->branch->id, 'name' => 'PAYE']);

        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2_000_000,
        ]);
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $paye->id,
            'payment_date' => now()->toDateString(),
            'amount' => 400_000,
        ]);

        $response = $this->getJson('/api/tax-payments/analytics');

        $response->assertStatus(200);
        $byCategory = collect($response->json('analytics.by_category'));
        $this->assertEquals(2, $byCategory->count());
        $this->assertEquals(2_000_000, (float) $byCategory->firstWhere('tax_category_id', $this->category->id)['total']);
        $this->assertEquals(400_000, (float) $byCategory->firstWhere('tax_category_id', $paye->id)['total']);
    }

    public function test_analytics_filters_by_period(): void
    {
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1_000_000,
        ]);
        TaxPayment::factory()->create([
            'business_branch_id' => $this->branch->id,
            'tax_category_id' => $this->category->id,
            'payment_date' => now()->subDays(40)->toDateString(),
            'amount' => 700_000,
        ]);

        $response = $this->getJson('/api/tax-payments/analytics?period=this_month');

        $response->assertStatus(200)
            ->assertJsonPath('analytics.total_paid', 1_000_000)
            ->assertJsonPath('analytics.total_count', 1);
    }

    public function test_user_cannot_access_another_branch_payment(): void
    {
        $foreignPayment = TaxPayment::factory()->create([
            'business_branch_id' => $this->otherBranch->id,
            'tax_category_id' => $this->otherCategory->id,
        ]);

        $this->getJson("/api/tax-payments/{$foreignPayment->id}")->assertStatus(404);
        $this->putJson("/api/tax-payments/{$foreignPayment->id}", ['amount' => 1])->assertStatus(404);
        $this->deleteJson("/api/tax-payments/{$foreignPayment->id}")->assertStatus(404);
    }
}
