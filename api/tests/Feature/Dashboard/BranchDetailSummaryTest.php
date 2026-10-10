<?php

namespace Tests\Feature\Dashboard;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * dashboard/branches/{id} renders four summary cards (workers, products, income,
 * expenses). They read worker_count/product_count/total_sales/total_expenses off the
 * branch payload, which the show() method never set — so the page always showed zero
 * for a figure it was handed no key for.
 *
 * The counts must also survive the branch global scope: an executive pinned to branch 1
 * opens branch 2's page and has to see branch 2's numbers, not an empty set.
 */
class BranchDetailSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branchOne;

    private BusinessBranch $branchTwo;

    private User $executive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branchOne = BusinessBranch::factory()->create(['business_id' => $this->business->id]);
        $this->branchTwo = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $this->executive = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branchOne->id,
            'role_id' => Role::factory()->create([
                'business_id' => $this->business->id,
                'name' => 'Executive',
            ])->id,
        ]);

        $this->actingAs($this->executive);
    }

    private function seedBranchTwo(): void
    {
        $workerUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branchTwo->id,
            'role_id' => $this->executive->role_id,
        ]);

        Worker::create([
            'user_id' => $workerUser->id,
            'employee_code' => 'EMP-00091',
        ]);

        Product::factory()->create([
            'business_branch_id' => $this->branchTwo->id,
            'quantity' => 5,
        ]);
        Product::factory()->create([
            'business_branch_id' => $this->branchTwo->id,
            'quantity' => 7,
        ]);

        Sale::create([
            'business_branch_id' => $this->branchTwo->id,
            'user_id' => $this->executive->id,
            'customer_id' => null,
            'subtotal' => 3000,
            'tax_amount' => 0,
            'total_amount' => 3000,
            'status' => 'completed',
            'note' => 'branch two sale',
        ]);

        $category = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Rent',
            'status' => 'active',
        ]);

        Expense::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branchTwo->id,
            'expense_category_id' => $category->id,
            'amount' => 750,
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
            'created_by' => $this->executive->id,
        ]);
    }

    public function test_branch_show_returns_the_summary_the_page_renders(): void
    {
        $this->seedBranchTwo();

        $response = $this->getJson("/api/dashboard/branches/{$this->branchTwo->id}");

        $response->assertOk();
        $branch = $response->json('branch');

        $this->assertSame(1, $branch['worker_count']);
        $this->assertSame(2, $branch['product_count']);
        $this->assertEquals(3000, $branch['total_sales']);
        $this->assertEquals(750, $branch['total_expenses']);
    }

    /**
     * The executive is pinned to branch 1 by onboarding. Without dropping the branch
     * scope on each count, branch 2's page would report zeroes for everything.
     */
    public function test_counts_are_not_filtered_by_the_viewers_own_branch(): void
    {
        $this->seedBranchTwo();

        $response = $this->getJson("/api/dashboard/branches/{$this->branchTwo->id}");

        $response->assertOk();

        $branch = $response->json('branch');

        $this->assertSame(1, $branch['worker_count'], 'worker_count was scoped to the viewer\'s branch');
        $this->assertSame(2, $branch['product_count'], 'product_count was scoped to the viewer\'s branch');
    }

    public function test_counts_only_cover_the_branch_being_viewed(): void
    {
        $this->seedBranchTwo();

        $response = $this->getJson("/api/dashboard/branches/{$this->branchOne->id}");

        $response->assertOk();
        $branch = $response->json('branch');

        $this->assertSame(0, $branch['worker_count']);
        $this->assertSame(0, $branch['product_count']);
        $this->assertEquals(0, $branch['total_sales']);
        $this->assertEquals(0, $branch['total_expenses']);
    }

    public function test_a_branch_of_another_business_is_still_unreachable(): void
    {
        $foreignBusiness = Business::factory()->create();
        $foreignBranch = BusinessBranch::factory()->create(['business_id' => $foreignBusiness->id]);

        $this->getJson("/api/dashboard/branches/{$foreignBranch->id}")
            ->assertStatus(404);
    }
}
