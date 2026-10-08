<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The sixth card on the executive analytics page, and the analytics block on the
 * Operations page, both read GET /api/products/analytics.
 *
 * ProductService::analytics() used to also build a `topProducts` list ranking products
 * by realised profit. The query was `SUM(quantity * (selling_price - cost_price))`
 * evaluated over sale_items, and sale_items has neither column -- they live on products.
 * The closure's select() replaced the columns Laravel had added and the framework's
 * trimming guard did not fire, so Postgres raised 42703, inventoryAnalytics() caught it
 * and answered 500, and the card rendered its error state.
 *
 * The list is removed rather than repaired. No consumer read it: the two components that
 * call this endpoint read statusBreakdown, lowStock, outOfStock and the totals, and the
 * top-products components in the app all read a `top_products` key from different
 * endpoints. And sale_items stores no cost, so a per-sale profit can only be derived from
 * the product's current cost_price, which is not the cost the sale was made at -- a
 * correct answer needs a cost-at-sale column, which is a schema decision, not a bug fix.
 *
 * So the test pins the endpoint answering 200 and carrying the keys its consumers read.
 */
class InventoryAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'Executive']);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_the_inventory_analytics_endpoint_answers_200(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $this->getJson('/api/products/analytics')->assertOk();
    }

    /**
     * The keys the two real consumers read. Asserted together because a card that renders
     * a dash for a figure it was handed is the failure mode this endpoint has.
     */
    public function test_it_carries_the_keys_its_consumers_read(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $response = $this->getJson('/api/products/analytics')->assertOk();

        foreach ([
            'totalInventoryValue',
            'totalPotentialRevenue',
            'totalExpectedProfit',
            'lowStock',
            'outOfStock',
            'statusBreakdown',
            'slowMoving',
            'deadStock',
            'fastMoving',
            'poorMarginProducts',
        ] as $key) {
            $this->assertArrayHasKey($key, $response->json('data'));
        }
    }

    public function test_the_totals_are_derived_from_the_products_on_the_branch(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $response = $this->getJson('/api/products/analytics')->assertOk();

        $this->assertEquals(10000.0, (float) $response->json('data.totalInventoryValue'));
        $this->assertEquals(15000.0, (float) $response->json('data.totalPotentialRevenue'));
        $this->assertEquals(5000.0, (float) $response->json('data.totalExpectedProfit'));
    }

    /**
     * The removed list is asserted absent so that reintroducing it is a decision rather
     * than an accident. See the class comment for why it cannot be repaired as written.
     */
    public function test_it_no_longer_returns_the_unreadable_top_products_list(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 1500,
        ]);

        $response = $this->getJson('/api/products/analytics')->assertOk();

        $this->assertArrayNotHasKey('topProducts', $response->json('data'));
    }
}