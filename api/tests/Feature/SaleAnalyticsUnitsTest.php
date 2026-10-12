<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Sales analytics figures, pinned while the unit totals moved from a PHP-side sum over
 * a hydrated relation to a single aggregate query.
 *
 * The optimisation is only safe if it produces the same numbers, including for the
 * awkward cases: a sale with no line items, and a sale whose items sum to zero.
 */
class SaleAnalyticsUnitsTest extends TestCase
{
    use RefreshDatabase;

    private BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id, 'name' => 'Executive']);

        Sanctum::actingAs(User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]));
    }

    private function sale(float $total, string $when, array $lines = []): Sale
    {
        $sale = Sale::create([
            'business_branch_id' => $this->branch->id,
            'subtotal' => $total,
            'total_amount' => $total,
            'status' => 'completed',
            'created_at' => $when,
            'updated_at' => $when,
        ]);

        foreach ($lines as $productId => $quantity) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => 1000,
                'subtotal' => $quantity * 1000,
            ]);
        }

        return $sale;
    }

    public function test_units_sold_totals_every_line(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 100,
            'status' => 'active',
        ]);

        $this->sale(3000, now()->subDays(2)->toDateTimeString(), [$product->id => 3]);
        $this->sale(5000, now()->subDay()->toDateTimeString(), [$product->id => 5]);

        $analytics = $this->getJson('/api/sales/branch-sales/analytics')->assertStatus(200)->json('data');

        $this->assertEquals(8, $analytics['items_sold']);
        $this->assertEquals(2, $analytics['total_transactions']);
        $this->assertEquals(8000.0, $analytics['gross_sales']);
    }

    public function test_a_sale_with_no_lines_contributes_nothing_and_still_counts(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 100,
            'status' => 'active',
        ]);

        $this->sale(3000, now()->subDays(2)->toDateTimeString(), [$product->id => 3]);
        // No lines at all. It must appear in the transaction count and the money, and
        // must not subtract from the unit total — a sparse map read as `?? 0`, not as
        // a missing entry collapsing the sum.
        $this->sale(2000, now()->subDay()->toDateTimeString());

        $analytics = $this->getJson('/api/sales/branch-sales/analytics')->assertStatus(200)->json('data');

        $this->assertEquals(3, $analytics['items_sold']);
        $this->assertEquals(2, $analytics['total_transactions']);
        $this->assertEquals(5000.0, $analytics['gross_sales']);
    }

    public function test_the_trend_points_sum_to_the_total(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 100,
            'status' => 'active',
        ]);

        $this->sale(3000, now()->subDays(2)->toDateTimeString(), [$product->id => 3]);
        $this->sale(5000, now()->subDay()->toDateTimeString(), [$product->id => 5]);

        $analytics = $this->getJson('/api/sales/branch-sales/analytics')->assertStatus(200)->json('data');

        $trendItems = collect($analytics['sales_trend'])->sum('items');

        $this->assertEquals(
            $analytics['items_sold'],
            $trendItems,
            'the per-day unit points must still add up to the period total'
        );
    }

    public function test_return_analytics_counts_the_same_units(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 100,
            'status' => 'active',
        ]);

        $this->sale(3000, now()->subDay()->toDateTimeString(), [$product->id => 7]);

        $analytics = $this->getJson('/api/sales/branch-sales/return-analytics')->assertStatus(200)->json('data');

        $this->assertEquals(7, $analytics['total_sold_quantity']);
    }
}
