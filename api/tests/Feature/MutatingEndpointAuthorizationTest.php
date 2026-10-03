<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\StockTransfer;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Item 5 of the review: controllers that had no effective authorization.
 *
 * Every endpoint below had either no role gate or a validation-only "authorize",
 * so any signed-in account in the business could reach it. The pattern in this
 * codebase is that hiding a button is not the control — the endpoint is.
 *
 * What each gate is protecting:
 *   - Coupons and promotions are margin given away at the till, so Operations is
 *     held out of authoring them.
 *   - Purchase receiving and stock transfers move real quantity and rewrite cost
 *     price, so they follow the stock capability rather than mere authentication.
 *   - Subscriptions and subscription payments are billing; completing a payment
 *     credits subscription_balance directly onto the business.
 */
class MutatingEndpointAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected BusinessBranch $branch;

    /** The user put on the session by the most recent actingAsRole() call. */
    private User $currentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);

        Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'supplier',
        ]);
    }

    private function actingAsRole(string $roleName): User
    {
        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => $roleName,
        ]);

        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $this->currentUser = $user;

        return $user;
    }

    // ---------------------------------------------------------------- coupons

    /**
     * No CouponFactory exists, so rows are built here. code is globally unique and
     * business_branch_id is a NOT NULL constrained foreign key, so both are supplied.
     */
    private function coupon(array $overrides = []): Coupon
    {
        static $sequence = 0;
        $sequence++;

        return Coupon::create(array_merge([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'code' => 'CPC'.$sequence,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
        ], $overrides));
    }

    private function couponPayload(): array
    {
        return [
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
        ];
    }

    public function test_operations_cannot_create_a_coupon(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/coupons', $this->couponPayload())->assertForbidden();

        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_operations_cannot_edit_a_coupon(): void
    {
        $this->actingAsRole('Operations');
        $coupon = $this->coupon();

        $this->putJson("/api/coupons/{$coupon->id}", ['discount_value' => 99])->assertForbidden();

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'discount_value' => $coupon->discount_value]);
    }

    public function test_operations_cannot_delete_a_coupon(): void
    {
        $this->actingAsRole('Operations');
        $coupon = $this->coupon();

        $this->deleteJson("/api/coupons/{$coupon->id}")->assertForbidden();

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    public function test_an_executive_can_create_a_coupon(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/coupons', $this->couponPayload())->assertCreated();

        $this->assertDatabaseCount('coupons', 1);
    }

    public function test_a_branch_manager_can_create_a_coupon(): void
    {
        $this->actingAsRole('BranchManager');

        $this->postJson('/api/coupons', $this->couponPayload())->assertCreated();
    }

    // ------------------------------------------------------------- promotions

    /**
     * PromotionFactory::definition() is empty, so it produces a row that violates the
     * NOT NULL title constraint. Building the row directly keeps the test honest about
     * which columns the table actually requires.
     */
    private function promotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'title' => 'Autumn sale',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ], $overrides));
    }

    private function promotionPayload(): array
    {
        return [
            'title' => 'Spring sale',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ];
    }

    public function test_operations_cannot_create_a_promotion(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/promotions', $this->promotionPayload())->assertForbidden();

        $this->assertDatabaseCount('promotions', 0);
    }

    public function test_operations_cannot_edit_or_delete_a_promotion(): void
    {
        $this->actingAsRole('Operations');
        $promotion = $this->promotion();

        $this->putJson("/api/promotions/{$promotion->id}", ['discount_value' => 90])->assertForbidden();
        $this->deleteJson("/api/promotions/{$promotion->id}")->assertForbidden();

        $this->assertDatabaseHas('promotions', ['id' => $promotion->id]);
    }

    public function test_an_executive_can_create_a_promotion(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/promotions', $this->promotionPayload())->assertCreated();
    }

    // ------------------------------------------------------- purchase receive

    private function purchaseWithItems(): Purchase
    {
        // No ProductCategoryFactory or PurchaseFactory exists, and ProductCategory
        // carries no business_branch_id column, so these rows are built directly.
        // status is an enum defaulting to the literal 1, which violates the
        // 'active'/'inactive' check constraint, so it is always supplied here.
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Beverages',
            'status' => 'active',
        ]);

        // products has no business_id column: stock is per branch, so the tenant key
        // is business_branch_id only. BaseModel's creating hook fills it from the
        // authenticated user.
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'product_category_id' => $category->id,
            'quantity' => 0,
        ]);

        $supplier = Supplier::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $purchase = Purchase::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
            'total_amount' => 50000,
        ]);

        $item = $purchase->purchaseItems()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'cost_price' => 5000,
            'subtotal' => 50000,
        ]);

        return $purchase->fresh(['purchaseItems']);
    }

    public function test_operations_cannot_receive_a_purchase(): void
    {
        $this->actingAsRole('Operations');
        $purchase = $this->purchaseWithItems();
        $item = $purchase->purchaseItems->first();

        $this->postJson("/api/purchases/branch-purchases/{$purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $item->id, 'quantity' => 10],
            ],
        ])->assertForbidden();

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'pending']);
    }

    public function test_procurement_can_receive_a_purchase_and_stock_lands(): void
    {
        $this->actingAsRole('Procurement');
        $purchase = $this->purchaseWithItems();
        $item = $purchase->purchaseItems->first();

        $this->postJson("/api/purchases/branch-purchases/{$purchase->id}/receive", [
            'items' => [
                ['purchase_item_id' => $item->id, 'quantity' => 10],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'completed']);
        $this->assertDatabaseHas('products', ['id' => $item->product_id, 'quantity' => 10]);
    }

    // ------------------------------------------------------- stock transfers

    private function stockTransfer(User $actor): StockTransfer
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Dry goods',
            'status' => 'active',
        ]);

        // The source has to be the acting user's own branch. StockTransferService
        // dispatch() re-reads the source product through Product's global scope, and
        // EffectiveBranchScope hides rows outside the caller's branch — so a transfer
        // out of a branch the user does not belong to fails with "No query results for
        // model [Product]" rather than exercising the authorization it was meant to.
        $toBranch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'product_category_id' => $category->id,
            'quantity' => 50,
        ]);

        // transferred_by is NOT NULL — the service stamps it from the authenticated
        // user on create, so the row has to be built as that user.
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_branch_id' => $this->branch->id,
            'to_branch_id' => $toBranch->id,
            'transferred_by' => $actor->id,
            'status' => 'draft',
        ]);

        $transfer->items()->create([
            'product_id' => $product->id,
            'quantity_expected' => 5,
        ]);

        return $transfer->fresh(['items']);
    }

    public function test_operations_cannot_dispatch_a_stock_transfer(): void
    {
        $this->actingAsRole('Operations');
        $transfer = $this->stockTransfer($this->currentUser);

        $this->postJson("/api/stock-transfers/{$transfer->id}/dispatch")->assertForbidden();

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'draft']);
    }

    public function test_operations_cannot_create_a_stock_transfer(): void
    {
        $this->actingAsRole('Operations');

        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Snacks',
            'status' => 'active',
        ]);

        $fromBranch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);
        $toBranch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $product = Product::factory()->create([
            'business_branch_id' => $fromBranch->id,
            'product_category_id' => $category->id,
            'quantity' => 50,
        ]);

        // A fully valid payload, so a 403 proves the role gate rejected it rather than
        // validation getting there first with a 422.
        $this->postJson('/api/stock-transfers', [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity_expected' => 5],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('stock_transfers', 0);
    }

    public function test_operations_cannot_cancel_or_delete_a_stock_transfer(): void
    {
        $this->actingAsRole('Operations');
        $transfer = $this->stockTransfer($this->currentUser);

        $this->postJson("/api/stock-transfers/{$transfer->id}/cancel")->assertForbidden();
        $this->deleteJson("/api/stock-transfers/{$transfer->id}")->assertForbidden();

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'draft']);
    }

    public function test_an_executive_can_dispatch_a_stock_transfer(): void
    {
        $this->actingAsRole('Executive');
        $transfer = $this->stockTransfer($this->currentUser);

        $this->postJson("/api/stock-transfers/{$transfer->id}/dispatch")->assertOk();

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'in_transit']);
    }

    /**
     * cash_flows.stock_transfer_id had no migration, so CashFlowService threw
     * SQLSTATE[42703] on every dispatch. StockTransferController catches \Exception
     * and answers 422, which read as a business-rule refusal rather than a broken
     * schema — the transfer silently stayed in draft and stock never moved.
     *
     * Asserting the cash-flow row is what pins the column back in place.
     */
    public function test_dispatching_a_stock_transfer_writes_its_cash_flow_row(): void
    {
        $this->actingAsRole('Executive');
        $transfer = $this->stockTransfer($this->currentUser);

        $this->postJson("/api/stock-transfers/{$transfer->id}/dispatch")->assertOk();

        $this->assertDatabaseHas('cash_flows', [
            'stock_transfer_id' => $transfer->id,
            'business_id' => $this->business->id,
            'type' => 'expense',
            'category' => 'stock_transfer',
        ]);
    }

    // ----------------------------------------------------------- subscriptions

    private function plan(): Plan
    {
        return Plan::create([
            'name' => 'Standard',
            'slug' => 'standard-'.uniqid(),
            'mark' => 'Affordable',
            'monthly_price' => 50000,
            'yearly_price' => 500000,
            'billing_cycle' => 'monthly',
        ]);
    }

    private function subscriptionFor(Business $business): Subscription
    {
        return Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $this->plan()->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function test_operations_cannot_create_a_subscription(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/subscriptions/business-subscriptions', [
            'business_id' => $this->business->id,
            'plan_id' => $this->plan()->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_a_branch_manager_cannot_create_a_subscription(): void
    {
        // Deliberately not canManageBranch: a subscription is what the business pays
        // to exist, so a branch manager must not rewrite the whole business' plan.
        $this->actingAsRole('BranchManager');

        $this->postJson('/api/subscriptions/business-subscriptions', [
            'business_id' => $this->business->id,
            'plan_id' => $this->plan()->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_an_executive_can_create_a_subscription(): void
    {
        $this->actingAsRole('Executive');
        $plan = $this->plan();

        $this->postJson('/api/subscriptions/business-subscriptions', [
            'business_id' => $this->business->id,
            'plan_id' => $plan->id,
        ])->assertCreated();

        $this->assertDatabaseHas('subscriptions', [
            'business_id' => $this->business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_operations_cannot_update_or_delete_a_subscription(): void
    {
        $this->actingAsRole('Operations');
        $subscription = $this->subscriptionFor($this->business);

        $this->putJson("/api/subscriptions/business-subscriptions/{$subscription->id}", [
            'status' => 'cancelled',
        ])->assertForbidden();

        $this->deleteJson("/api/subscriptions/business-subscriptions/{$subscription->id}")->assertForbidden();

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'active']);
    }

    public function test_a_subscription_cannot_be_repointed_at_another_business(): void
    {
        $this->actingAsRole('Executive');
        $subscription = $this->subscriptionFor($this->business);
        $otherBusiness = Business::factory()->create();

        // business_id is no longer an updatable field, so the move is rejected at
        // validation rather than leaving the row in the wrong tenant's scope.
        $this->putJson("/api/subscriptions/business-subscriptions/{$subscription->id}", [
            'business_id' => $otherBusiness->id,
        ])->assertStatus(422);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'business_id' => $this->business->id,
        ]);
    }

    public function test_an_executive_can_read_its_own_subscriptions(): void
    {
        $this->actingAsRole('Executive');
        $subscription = $this->subscriptionFor($this->business);

        $this->getJson('/api/subscriptions/business-subscriptions')->assertOk();
        $this->getJson("/api/subscriptions/business-subscriptions/{$subscription->id}")->assertOk();
    }

    // -------------------------------------------------- subscription payments

    /**
     * payment_methods.method is a NOT NULL enum, so it has to be supplied. The column
     * is unique per business, so each helper call picks the next free value.
     */
    private function enabledPaymentMethod(): PaymentMethod
    {
        $taken = PaymentMethod::where('business_id', $this->business->id)
            ->pluck('method')
            ->all();

        $method = collect(['mobile_money', 'card', 'cash', 'credit', 'cryptocurrency'])
            ->first(fn ($candidate) => ! in_array($candidate, $taken, true));

        return PaymentMethod::create([
            'business_id' => $this->business->id,
            'method' => $method,
            'status' => 'enabled',
        ]);
    }

    private function paymentFor(Subscription $subscription): SubscriptionPayment
    {
        return SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'payment_method_id' => $this->enabledPaymentMethod()->id,
            'amount_paid' => 50000,
            'payment_status' => 'pending',
            // Set explicitly rather than left to BaseModel's creating hook. The hook
            // reads the tenant from the authenticated user, and these rows are built
            // before actingAsRole() in some tests — a null business_id would then put
            // the row outside the tenant scope and every request would answer 404.
            'business_id' => $this->business->id,
        ]);
    }

    public function test_operations_cannot_record_a_subscription_payment(): void
    {
        $this->actingAsRole('Operations');
        $subscription = $this->subscriptionFor($this->business);
        $method = $this->enabledPaymentMethod();

        $this->postJson('/api/subscription-payments', [
            'subscription_id' => $subscription->id,
            'payment_method_id' => $method->id,
            'amount_paid' => 50000,
        ])->assertForbidden();

        $this->assertDatabaseCount('subscription_payments', 0);
    }

    public function test_operations_cannot_complete_a_subscription_payment(): void
    {
        // The write that credits subscription_balance onto the business.
        $this->actingAsRole('Operations');
        $payment = $this->paymentFor($this->subscriptionFor($this->business));

        $balanceBefore = $this->business->fresh()->subscription_balance;

        $this->putJson("/api/subscription-payments/{$payment->id}", [
            'payment_status' => 'completed',
        ])->assertForbidden();

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'payment_status' => 'pending',
        ]);
        $this->assertSame(
            (float) $balanceBefore,
            (float) $this->business->fresh()->subscription_balance,
            'A forbidden verify must not move the balance.'
        );
    }

    public function test_an_executive_can_complete_a_subscription_payment_and_the_balance_lands(): void
    {
        $this->actingAsRole('Executive');
        $payment = $this->paymentFor($this->subscriptionFor($this->business));

        $this->putJson("/api/subscription-payments/{$payment->id}", [
            'payment_status' => 'completed',
        ])->assertOk();

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'payment_status' => 'completed',
        ]);
        $this->assertSame(
            (float) $this->business->fresh()->subscription_balance,
            (float) $payment->amount_paid
        );
    }

    public function test_completing_an_already_completed_payment_does_not_credit_twice(): void
    {
        $this->actingAsRole('Executive');
        $payment = $this->paymentFor($this->subscriptionFor($this->business));

        $this->putJson("/api/subscription-payments/{$payment->id}", [
            'payment_status' => 'completed',
        ])->assertOk();

        // Re-sending the same status used to increment subscription_balance again.
        $this->putJson("/api/subscription-payments/{$payment->id}", [
            'payment_status' => 'completed',
        ])->assertOk();

        $this->assertSame(
            (float) $payment->amount_paid,
            (float) $this->business->fresh()->subscription_balance,
            'Verifying the same payment twice must credit once.'
        );
    }

    public function test_operations_cannot_delete_a_subscription_payment(): void
    {
        $this->actingAsRole('Operations');
        $payment = $this->paymentFor($this->subscriptionFor($this->business));

        $this->deleteJson("/api/subscription-payments/{$payment->id}")->assertForbidden();

        $this->assertDatabaseHas('subscription_payments', ['id' => $payment->id]);
    }

    public function test_a_rejected_payment_still_requires_a_reason(): void
    {
        $this->actingAsRole('Executive');
        $payment = $this->paymentFor($this->subscriptionFor($this->business));

        $this->putJson("/api/subscription-payments/{$payment->id}", [
            'payment_status' => 'rejected',
        ])->assertStatus(422);
    }

    // ------------------------------------------- central delete allowlist

    /**
     * The DELETE deny-rule was `isRestricted()` — "deny Operations". That is only
     * correct while Operations is the sole role without delete rights, and it is not:
     * Procurement has none either, but being absent from RESTRICTED_ROLES it sailed
     * through every DELETE in the app, tenant-wide finance rows included.
     *
     * Procurement is the case worth pinning because it is a legitimate, non-restricted
     * role that people expect to be able to work — it is exactly the role a denylist
     * silently waves through.
     */
    public function test_procurement_cannot_delete_a_record(): void
    {
        $this->actingAsRole('Procurement');
        $coupon = $this->coupon();

        $this->deleteJson("/api/coupons/{$coupon->id}")->assertForbidden();

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    public function test_procurement_cannot_delete_a_subscription_payment(): void
    {
        $this->actingAsRole('Procurement');
        $payment = $this->paymentFor($this->subscriptionFor($this->business));

        $this->deleteJson("/api/subscription-payments/{$payment->id}")->assertForbidden();

        $this->assertDatabaseHas('subscription_payments', ['id' => $payment->id]);
    }

    public function test_a_branch_manager_may_still_delete_within_their_branch(): void
    {
        $this->actingAsRole('BranchManager');
        $coupon = $this->coupon();

        $this->deleteJson("/api/coupons/{$coupon->id}")->assertOk();

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    // ------------------------------------------------------- stock transfer edit

    /**
     * routes/stock-transfers.php has always published PUT/PATCH to update(), but no
     * method answered it, so the route was a 500 waiting to happen.
     */
    public function test_a_draft_stock_transfer_can_be_edited_by_an_executive(): void
    {
        $this->actingAsRole('Executive');
        $transfer = $this->stockTransfer($this->currentUser);

        $this->putJson("/api/stock-transfers/{$transfer->id}", [
            'notes' => 'Hand-delivered',
            'transport_cost' => 15,
        ])->assertOk();

        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'notes' => 'Hand-delivered',
            'transport_cost' => 15,
        ]);
    }

    public function test_operations_cannot_edit_a_stock_transfer(): void
    {
        $this->actingAsRole('Operations');
        $transfer = $this->stockTransfer($this->currentUser);

        $this->putJson("/api/stock-transfers/{$transfer->id}", [
            'notes' => 'Tampered',
        ])->assertForbidden();

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'notes' => $transfer->notes]);
    }

    /**
     * Once stock has moved the header is a stock_movements fact. Letting status be
     * rewritten here would leave the ledger describing a movement the header denies.
     */
    public function test_a_dispatched_stock_transfer_can_no_longer_be_edited(): void
    {
        $this->actingAsRole('Executive');
        $transfer = $this->stockTransfer($this->currentUser);

        $this->postJson("/api/stock-transfers/{$transfer->id}/dispatch")->assertOk();

        $this->putJson("/api/stock-transfers/{$transfer->id}", [
            'notes' => 'Rewritten after dispatch',
        ])->assertStatus(422);

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'in_transit']);
    }

    // --------------------------------------------------------- assistant access

    public function test_operations_cannot_reach_the_ai_assistant(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/ai/chat', ['message' => 'Summarise today'])
            ->assertForbidden();
    }
}
