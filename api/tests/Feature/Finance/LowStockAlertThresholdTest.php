<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\SaleItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The low-stock alert has to describe the stock the sale leaves behind.
 *
 * SaleItemService checked `$product->quantity <= $product->reorder_level` before the
 * decrement happened further down, so it alerted on a number that was about to change and
 * stayed silent about one that had. A product at 11 with a reorder level of 10, selling
 * 3, lands on 8 — nothing fired, and nobody was told. PosService::checkout decrements
 * first and then checks, so the till never had this; the non-POS sale path did.
 */
class LowStockAlertThresholdTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $business->id]);

        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create([
                'business_id' => $business->id,
                'name' => 'Executive',
            ])->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $business->id,
            'name' => 'Cash',
            'method' => 'cash',
            'status' => 'enabled',
        ]);
    }

    private PaymentMethod $paymentMethod;

    private function product(int $quantity, int $reorderLevel): Product
    {
        return Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => $quantity,
            'reorder_level' => $reorderLevel,
            'status' => 'active',
        ]);
    }

    private function sell(Product $product, int $quantity): void
    {
        app(SaleItemService::class)->handleSaveSaleItem([
            'business_branch_id' => $this->branch->id,
            'customer_id' => null,
            'note' => null,
            'paymentStatus' => 'paid',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => 10000],
            ],
        ], $this->branch->id);
    }

    public function test_it_alerts_when_the_sale_takes_stock_below_the_threshold(): void
    {
        // 11 in stock, threshold 10, selling 3 leaves 8. The old check saw 11 > 10 and
        // said nothing at all.
        $product = $this->product(11, 10);

        $this->sell($product, 3);

        $alert = Notification::where('type', 'low_stock')->latest('id')->first();

        $this->assertNotNull($alert, 'Selling below the reorder level must raise an alert.');
        $this->assertSame(8, $alert->data['current_stock'], 'The alert must report the stock left, not the stock it started with.');
        $this->assertSame(8, $product->fresh()->quantity);
    }

    public function test_the_alert_names_the_surviving_quantity(): void
    {
        $product = $this->product(5, 4);

        $this->sell($product, 2);

        $alert = Notification::where('type', 'low_stock')->latest('id')->first();

        $this->assertNotNull($alert);
        $this->assertStringContainsString('Only 3 left', $alert->message);
    }

    public function test_it_stays_quiet_while_stock_remains_above_the_threshold(): void
    {
        // 20 in stock, threshold 10, selling 3 leaves 17.
        $this->sell($this->product(20, 10), 3);

        $this->assertSame(0, Notification::where('type', 'low_stock')->count());
    }

    public function test_a_sale_split_across_two_lines_is_assessed_on_the_total(): void
    {
        // Two lines for the same product, 2 + 1 = 3 sold, so 11 leaves 8.
        $product = $this->product(11, 10);

        app(SaleItemService::class)->handleSaveSaleItem([
            'business_branch_id' => $this->branch->id,
            'customer_id' => null,
            'note' => null,
            'paymentStatus' => 'paid',
            'payment_status_id' => $this->paymentMethod->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000],
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000],
            ],
        ], $this->branch->id);

        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertNotNull(
            Notification::where('type', 'low_stock')->first(),
            'The two lines are one sale and must be assessed once, on the total.'
        );
    }
}
