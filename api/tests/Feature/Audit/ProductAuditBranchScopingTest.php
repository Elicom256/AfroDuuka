<?php

namespace Tests\Feature\Audit;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The audit dialogs offer a branch dropdown, but the product list they feed it used to
 * come from GET /api/products with no branch parameter, so the two could disagree: an
 * executive could be shown branch-2's products while creating an audit on branch 1 (or
 * the reverse), and a product id from the wrong branch passed the bare
 * `exists:products,id` rule only to fail deep inside ProductAuditService::createAudit()
 * as a scope-aware ModelNotFound 404.
 *
 * The store request now ties every item's product to the audit's branch, so a
 * cross-branch product fails cleanly at validation (422) and never reaches that 404.
 * GET /api/products also honours ?business_branch_id so the dialog can pull exactly the
 * branch's products in the first place.
 */
class ProductAuditBranchScopingTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branchA;

    private BusinessBranch $branchB;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branchA = BusinessBranch::factory()->create(['business_id' => $this->business->id]);
        $this->branchB = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'executive',
        ]);

        // Unpinned executive: allowed (for validation) every branch of the business, so
        // branch B is a legitimate choice for the dropdown -- which is exactly why the
        // product branch pinning matters rather than being caught by the branch scope.
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_product_list_can_be_filtered_to_one_branch(): void
    {
        $this->actingAs($this->user, 'sanctum');

        Product::factory()->create([
            'business_branch_id' => $this->branchA->id,
            'name' => 'Branch A widget',
        ]);
        Product::factory()->create([
            'business_branch_id' => $this->branchB->id,
            'name' => 'Branch B widget',
        ]);

        $this->getJson('/api/products?business_branch_id='.$this->branchB->id)
            ->assertOk()
            ->assertJsonPath('products.*.name', ['Branch B widget']);
    }

    public function test_an_audit_item_from_a_different_branch_is_rejected_with_422(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Belongs to branch B; the audit is for branch A.
        $productB = Product::factory()->create([
            'business_branch_id' => $this->branchB->id,
            'quantity' => 10,
        ]);

        $payload = [
            'business_branch_id' => $this->branchA->id,
            'audit_date' => now()->toDateString(),
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $productB->id,
                    'counted_quantity' => 8,
                ],
            ],
        ];

        $this->postJson('/api/product-audits', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.product_id');
    }

    public function test_an_audit_item_from_the_same_branch_is_accepted(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $productA = Product::factory()->create([
            'business_branch_id' => $this->branchA->id,
            'quantity' => 10,
        ]);

        $payload = [
            'business_branch_id' => $this->branchA->id,
            'audit_date' => now()->toDateString(),
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'counted_quantity' => 8,
                ],
            ],
        ];

        $this->postJson('/api/product-audits', $payload)->assertCreated();
    }
}