<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Customer;
use App\Models\CustomerCreditTransaction;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The tender has to cover the cart.
 *
 * Nothing enforced this. A client could POST a full cart with payments totalling less
 * than the sale and it completed: the sale was 'completed', its payment rows recorded
 * as 'paid', and the receipt showed the shortfall as zero change rather than as money
 * still owed. The goods had already left the counter, so this was revenue that never
 * arrived but was fully booked.
 */
class PosCheckoutPaymentCoverageTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branch;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        PaymentMethod::create([
            'business_id' => $this->business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        $this->product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 100,
            'cost_price' => 1000,
            'selling_price' => 1000,
            'reorder_level' => 1,
            'status' => 'active',
        ]);

        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'Executive']);

        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);
    }

    private function payload(array $payments): array
    {
        return [
            'business_branch_id' => $this->branch->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 1000, 'discount' => 0],
            ],
            'payments' => $payments,
            'currency' => 'UGX',
        ];
    }

    public function test_a_short_tender_is_refused_and_writes_nothing(): void
    {
        $this->postJson('/api/pos/checkout', $this->payload([
            ['method' => 'cash', 'amount' => 500],
        ]))->assertStatus(422);

        // Nothing may survive: not the sale, not its payments, and above all not the
        // stock decrement — the goods are still on the shelf.
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SalePayment::count());
        $this->assertSame(100, $this->product->fresh()->quantity);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_a_tender_one_cent_short_is_refused(): void
    {
        // 2000.00 needed, 1999.99 offered. Rounded to two places, not compared loosely —
        // a float comparison would let this through as "close enough".
        $this->postJson('/api/pos/checkout', $this->payload([
            ['method' => 'cash', 'amount' => 1999.99],
        ]))->assertStatus(422);

        $this->assertSame(0, Sale::count());
        $this->assertSame(100, $this->product->fresh()->quantity);
    }

    public function test_exact_payment_succeeds(): void
    {
        $this->postJson('/api/pos/checkout', $this->payload([
            ['method' => 'cash', 'amount' => 2000],
        ]))->assertStatus(200);

        $this->assertSame(1, Sale::count());
        $this->assertSame(98, $this->product->fresh()->quantity);
    }

    public function test_overpayment_succeeds_and_gives_change(): void
    {
        $this->postJson('/api/pos/checkout', $this->payload([
            ['method' => 'cash', 'amount' => 5000],
        ]))->assertStatus(200);

        $receipt = Receipt::firstOrFail();

        $this->assertEquals(2000.0, (float) $receipt->total);
        $this->assertEquals(5000.0, (float) $receipt->amount_paid);
        $this->assertEquals(3000.0, (float) $receipt->change_given);
    }

    public function test_split_tenders_are_summed_before_the_comparison(): void
    {
        // 1200 + 800 covers 2000 only as a total. Rejecting each line on its own would
        // be wrong; the check has to be against the tender, not any single payment.
        $this->postJson('/api/pos/checkout', $this->payload([
            ['method' => 'cash', 'amount' => 1200],
            ['method' => 'mobile_money', 'amount' => 800],
        ]))->assertStatus(200);

        $this->assertSame(1, Sale::count());
    }

    public function test_a_zero_value_payment_is_rejected(): void
    {
        // A 0.00 tender row would otherwise become a 'paid' SalePayment for nothing,
        // inflating the count of payment methods on the receipt.
        $this->postJson('/api/pos/checkout', $this->payload([
            ['method' => 'cash', 'amount' => 2000],
            ['method' => 'mobile_money', 'amount' => 0],
        ]))->assertStatus(422);

        $this->assertSame(0, Sale::count());
    }

    public function test_credit_counts_as_a_tender(): void
    {
        // Store credit is a legitimate tender: the charge is recorded against the
        // customer, so refusing it in the coverage check would break the flow rather
        // than protect revenue. It needs a customer to charge — see
        // CustomerCreditService::recordCharge.
        $customerUser = User::factory()->create(['business_id' => $this->business->id]);
        $customer = Customer::factory()->create([
            'user_id' => $customerUser->id,
            'business_id' => $this->business->id,
        ]);

        $this->postJson('/api/pos/checkout', [
            ...$this->payload([['method' => 'credit', 'amount' => 2000]]),
            'customer_id' => $customer->id,
        ])->assertStatus(200);

        $this->assertSame(1, Sale::count());
        $this->assertSame(1, CustomerCreditTransaction::count());
    }
}
