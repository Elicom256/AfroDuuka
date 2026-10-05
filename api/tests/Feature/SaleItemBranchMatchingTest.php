<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A sale may only draw on stock belonging to the branch it is recorded against.
 *
 * SaleItemService scopes its product lookup with `where('business_branch_id', $branchId)`
 * and then checks it found every product it was asked for, so a product from another
 * branch cannot be sold through this one. The guard is present in the service
 * (checked.md P2 lists it as missing — it was added alongside the `lockForUpdate()`
 * change) but nothing tested it, so this pins it.
 *
 * The second branch matters as much as the first: an Executive can sell from any branch
 * they choose, which is legitimate. What must never happen is branch A's stock moving
 * while the sale is booked against branch B, because the two branches then report
 * different stock for the same product row.
 */
class SaleItemBranchMatchingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessBranch $branch;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $this->otherBranch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create([
                'business_id' => $business->id,
                'name' => 'Executive',
            ])->id,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        Sanctum::actingAs($this->user);
    }

    private BusinessBranch $otherBranch;

    private function sellAgainst(BusinessBranch $against, Product $product, int $quantity = 1)
    {
        return app(SaleItemService::class)->handleSaveSaleItem([
            'business_branch_id' => $against->id,
            'customer_id' => null,
            'note' => 'Branch matching',
            'paymentStatus' => 'paid',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => 1500],
            ],
        ], $against->id);
    }

    public function test_it_refuses_a_product_belonging_to_another_branch(): void
    {
        $theirs = Product::factory()->create([
            'business_branch_id' => $this->otherBranch->id,
            'quantity' => 10,
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        try {
            $this->sellAgainst($this->branch, $theirs);

            $this->fail('Expected the sale to be rejected: the product belongs to another branch.');
        } catch (\Exception $exception) {
            $this->assertSame(422, $exception->getCode());
            $this->assertStringContainsString('do not belong to the selected branch', $exception->getMessage());
        }

        // Nothing moved, and nothing was written.
        $this->assertSame(10, $theirs->fresh()->quantity);
        $this->assertSame(0, Sale::where('business_branch_id', $this->branch->id)->count());
        $this->assertSame(0, StockMovement::where('product_id', $theirs->id)->count());
    }

    public function test_it_refuses_when_only_one_of_several_products_is_from_another_branch(): void
    {
        // The count guard, not the filter, is what catches this: the branch filter drops
        // the foreign product, so the lookup returns fewer rows than were asked for.
        $ours = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'selling_price' => 1500,
            'status' => 'active',
        ]);
        $theirs = Product::factory()->create([
            'business_branch_id' => $this->otherBranch->id,
            'quantity' => 10,
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        $this->expectExceptionCode(422);

        try {
            app(SaleItemService::class)->handleSaveSaleItem([
                'business_branch_id' => $this->branch->id,
                'customer_id' => null,
                'note' => 'Mixed branches',
                'paymentStatus' => 'paid',
                'payment_status_id' => $this->paymentMethod->id,
                'items' => [
                    ['product_id' => $ours->id, 'quantity' => 1, 'unit_price' => 1500],
                    ['product_id' => $theirs->id, 'quantity' => 1, 'unit_price' => 1500],
                ],
            ], $this->branch->id);
        } finally {
            // The whole sale is refused, so our own product must be untouched too.
            $this->assertSame(10, $ours->fresh()->quantity);
        }
    }

    public function test_it_allows_a_product_from_the_branch_being_sold_against(): void
    {
        $ours = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        $sale = $this->sellAgainst($this->branch, $ours, 2);

        $this->assertSame('completed', $sale->status);
        $this->assertSame(8, $ours->fresh()->quantity);
    }

    public function test_it_refuses_a_branch_the_caller_is_not_scoped_to(): void
    {
        // Written expecting this to be allowed, on the assumption that an Executive is not
        // branch-confined. It is: EffectiveBranchScope confines even a business executive
        // to an assigned set of branches, so naming a branch outside it is refused with
        // 403 before the product lookup is ever reached. Two independent guards, then:
        // which branches you may sell against, and which products that branch holds.
        $other = Product::factory()->create([
            'business_branch_id' => $this->otherBranch->id,
            'quantity' => 10,
            'selling_price' => 1500,
            'status' => 'active',
        ]);

        try {
            $this->sellAgainst($this->otherBranch, $other, 4);

            $this->fail('Expected the sale to be refused for a branch outside the caller\'s scope.');
        } catch (\Exception $exception) {
            $this->assertSame(403, $exception->getCode());
            $this->assertStringContainsString('not within your allowed scope', $exception->getMessage());
        }

        $this->assertSame(10, $other->fresh()->quantity);
    }
}
