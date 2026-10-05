<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessDebit;
use App\Models\CashDrawerSession;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\Coupon;
use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleOrder;
use App\Models\StockTransfer;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WhatsAppConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    protected Supplier $supplier;

    protected Customer $customer;

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

        $this->supplier = Supplier::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->customer = Customer::factory()->create([
            'business_id' => $this->business->id,
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

    // =====================================================================
    // The 29 that the first audit of item 5 never looked at.
    //
    // Everything above was found by reading controllers. This block is the other
    // half: it was found by walking the route table mechanically, and the reasons
    // are written out because "no gate" reads like an oversight rather than a
    // decision unless you can see which decision it is.
    //
    // Two patterns produced every one of these holes:
    //
    //   1. A FormRequest::authorize() returning Auth::check(). That is authentication.
    //      Ninety-six request classes answer that, and for most modules a route-level
    //      `role` group covers them. For the modules in this block nothing did.
    //   2. A twin controller that lost its gate. PurchaseOrderController gates every
    //      action inline; PurchaseController — the same resource, different table —
    //      gated only receive(). The copy that is *not* protected is the one nobody
    //      re-reads.
    //
    // Each refusal test asserts the row did not change, so a 403 that arrives before
    // the write cannot pass by accident.
    // =====================================================================

    // ------------------------------------------- payment provider credentials

    /** No PaymentGatewayFactory exists, so the row is built directly. */
    private function paymentGateway(array $overrides = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'business_id' => $this->business->id,
            'provider' => 'mtn_momo',
            'api_key' => 'live-key',
            'api_secret' => 'live-secret',
            'webhook_secret' => 'live-webhook',
        ], $overrides));
    }

    private function gatewayPayload(array $overrides = []): array
    {
        return array_merge([
            'business_id' => $this->business->id,
            'provider' => 'mtn_momo',
            'api_key' => 'attacker-key',
            'api_secret' => 'attacker-secret',
            'webhook_secret' => 'attacker-webhook',
        ], $overrides);
    }

    /**
     * The one worth naming in the suite forever.
     *
     * This row is where live mobile-money payments are routed and how an inbound
     * webhook proves it came from the provider rather than from anyone who guessed
     * the URL. An Operations account — the till role this review defines as "runs the
     * day-to-day floor, does not author the catalogue, does not remove records" —
     * could write it, and the audit that certified item 5 as done never ran this.
     */
    public function test_operations_cannot_write_a_payment_gateway(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/payment-gateways', $this->gatewayPayload())->assertForbidden();

        $this->assertDatabaseCount('payment_gateways', 0);
    }

    public function test_operations_cannot_rewrite_a_payment_gateway(): void
    {
        $this->actingAsRole('Operations');
        $gateway = $this->paymentGateway();

        $this->putJson("/api/payment-gateways/{$gateway->id}", [
            'api_key' => 'attacker-key',
            'webhook_secret' => 'attacker-webhook',
        ])->assertForbidden();

        $this->assertDatabaseHas('payment_gateways', [
            'id' => $gateway->id,
            'api_key' => 'live-key',
            'webhook_secret' => 'live-webhook',
        ]);
    }

    public function test_an_executive_can_write_a_payment_gateway(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/payment-gateways', $this->gatewayPayload())->assertCreated();

        $this->assertDatabaseHas('payment_gateways', [
            'business_id' => $this->business->id,
            'provider' => 'mtn_momo',
            'api_key' => 'attacker-key',
        ]);
    }

    /** canManagePaymentConfig() resolves to canManageBranch(), so this must hold too. */
    public function test_a_branch_manager_can_write_a_payment_gateway(): void
    {
        $this->actingAsRole('BranchManager');

        $this->postJson('/api/payment-gateways', $this->gatewayPayload())->assertCreated();
    }

    // ------------------------------------------------------------ currency rates

    private function currencyRate(array $overrides = []): CurrencyRate
    {
        return CurrencyRate::create(array_merge([
            'business_id' => $this->business->id,
            'base_currency' => 'UGX',
            'target_currency' => 'USD',
            'rate' => 3600,
            'source' => 'central-bank',
            'valid_from' => now()->toDateString(),
        ], $overrides));
    }

    private function currencyRatePayload(array $overrides = []): array
    {
        return array_merge([
            'business_id' => $this->business->id,
            'base_currency' => 'UGX',
            'target_currency' => 'USD',
            'rate' => 9999,
            'source' => 'attacker',
            'valid_from' => now()->toDateString(),
        ], $overrides);
    }

    /**
     * Every multi-currency total and report derives from this row, so writing one
     * repricing the whole business' books is a one-request operation.
     */
    public function test_operations_cannot_write_a_currency_rate(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/currency-rates', $this->currencyRatePayload())->assertForbidden();

        $this->assertDatabaseCount('currency_rates', 0);
    }

    public function test_operations_cannot_rewrite_a_currency_rate(): void
    {
        $this->actingAsRole('Operations');
        $rate = $this->currencyRate();

        $this->putJson("/api/currency-rates/{$rate->id}", ['rate' => 9999])->assertForbidden();

        $this->assertDatabaseHas('currency_rates', ['id' => $rate->id, 'rate' => 3600]);
    }

    public function test_an_executive_can_write_a_currency_rate(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/currency-rates', $this->currencyRatePayload())->assertCreated();
    }

    // -------------------------------------------------------- whatsapp credentials

    private function whatsAppPayload(array $overrides = []): array
    {
        return array_merge([
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'access_token' => 'attacker-token',
            'webhook_verify_token' => 'attacker-verify',
        ], $overrides);
    }

    public function test_operations_cannot_write_whatsapp_credentials(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/whatsapp', $this->whatsAppPayload())->assertForbidden();

        $this->assertDatabaseCount('whats_app_configs', 0);
    }

    /**
     * The stored config already holds a live token, so the refusal has to leave it
     * alone rather than merely answer 403.
     */
    public function test_operations_cannot_rewrite_whatsapp_credentials(): void
    {
        $this->actingAsRole('Operations');
        $config = WhatsAppConfig::create([
            'business_id' => $this->business->id,
            'provider' => 'meta',
            'business_phone' => '+256700000001',
            'access_token' => 'live-token',
        ]);

        $this->putJson("/api/whatsapp/{$config->id}", [
            'access_token' => 'attacker-token',
        ])->assertForbidden();

        // access_token is an encrypted cast, so only the accessor round-trips plaintext.
        $this->assertSame('live-token', $config->fresh()->access_token);
    }

    /**
     * Unmetered today because the provider is in demo mode, but the endpoint is the
     * outbound channel and is billed the moment a paid account is attached.
     */
    public function test_operations_cannot_send_a_whatsapp_test_message(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/whatsapp/test-message', [
            'recipient' => '+256700000002',
            'message' => 'Test',
        ])->assertForbidden();
    }

    public function test_a_branch_manager_can_write_whatsapp_credentials(): void
    {
        $this->actingAsRole('BranchManager');

        $this->postJson('/api/whatsapp', $this->whatsAppPayload())->assertCreated();
    }

    // ------------------------------------------------------------------- money

    private function businessDebitPayload(array $overrides = []): array
    {
        return array_merge([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 50000,
            'reference' => 'INV-900',
            'status' => 'open',
            'description' => 'Supplier goods on credit',
        ], $overrides);
    }

    public function test_operations_cannot_open_a_business_debit(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/finances/business-debits', $this->businessDebitPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('business_debits', 0);
    }

    public function test_operations_cannot_rewrite_a_business_debit(): void
    {
        $this->actingAsRole('Operations');
        $debit = BusinessDebit::factory()->create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 50000,
            'status' => 'open',
        ]);

        // Valid payload on purpose: the inline gate runs after UpdateBusinessDebitRequest,
        // so an incomplete body would be answered 422 and prove nothing about the role.
        $this->putJson("/api/finances/business-debits/{$debit->id}", [
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 1,
            'status' => 'open',
        ])->assertForbidden();

        $this->assertDatabaseHas('business_debits', ['id' => $debit->id, 'amount' => 50000]);
    }

    public function test_an_executive_can_open_a_business_debit(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/finances/business-debits', $this->businessDebitPayload())
            ->assertCreated();
    }

    /**
     * The counterpart to the above, and the reason store() and pay() are not the
     * same rule. pay() records a payment already committed and stays open to the
     * floor; opening the debt asserts the business owes the money, which it does not
     * until someone says so. BusinessDebitTest keeps its Operations user as the
     * evidence that the pay side stays open.
     */
    public function test_operations_can_still_settle_a_business_debit(): void
    {
        $this->actingAsRole('Operations');
        $debit = BusinessDebit::factory()->create([
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 30000,
            'status' => 'open',
        ]);

        $this->postJson("/api/finances/business-debits/{$debit->id}/pay", [
            'amount' => 30000,
        ])->assertCreated();

        $this->assertSame(0.0, (float) $debit->fresh()->balance());
    }

    public function test_operations_cannot_open_a_business_credit(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/finances/business-credits', [
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 75000,
            'reference' => 'CRED-900',
            'status' => 'open',
        ])->assertForbidden();

        $this->assertDatabaseCount('business_credits', 0);
    }

    public function test_an_executive_can_open_a_business_credit(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/finances/business-credits', [
            'business_branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 75000,
            'reference' => 'CRED-900',
            'status' => 'open',
        ])->assertCreated();
    }

    public function test_operations_cannot_record_a_customer_credit_payment(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson("/api/finances/customers/{$this->customer->id}/credit-payments", [
            'business_branch_id' => $this->branch->id,
            'amount' => 25000,
            'reference' => 'PAY-CRED-900',
        ])->assertForbidden();

        $this->assertDatabaseCount('customer_credit_transactions', 0);
    }

    /**
     * This one was not simply missing — it was answering 422 for a role refusal.
     * authorizeSensitiveFinance() sat inside the try block, so abort()'s
     * HttpException was caught by `catch (\Exception)` and reported as "Failed to
     * create adjustment". The write was blocked; the caller could not tell a refused
     * role from a bad payload.
     */
    public function test_operations_cannot_post_a_cash_flow_adjustment(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/finances/adjustments', [
            'transaction_code' => 'ADJ-900',
            'type' => 'adjustment',
            'amount' => 1000,
            'currency' => 'UGX',
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->currentUser->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('cash_flows', ['transaction_code' => 'ADJ-900']);
    }

    public function test_an_executive_can_post_a_cash_flow_adjustment(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/finances/adjustments', [
            'transaction_code' => 'ADJ-901',
            'type' => 'adjustment',
            'amount' => 1000,
            'currency' => 'UGX',
            // Required: an adjustment carries no type that implies which way the money
            // moved, so the sign has to be stated.
            'direction' => 'credit',
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->currentUser->id,
            // adjustment() returns the created flow without an explicit 201.
        ])->assertOk();
    }

    /**
     * A refused role must be answered 403 even when the payload is also invalid.
     *
     * When the role gates lived in the controller and validation ran first, an
     * Operations user posting an adjustment without a direction was told 422
     * "validation failed" instead of 403, hiding the refusal behind a payload problem.
     */
    public function test_a_refused_role_is_forbidden_even_with_an_invalid_payload(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/finances/adjustments', [
            'amount' => 1000,
            'currency' => 'UGX',
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->currentUser->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('cash_flows', 0);
    }

    public function test_operations_cannot_open_a_cash_drawer(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/finances/cash-drawers/open', [
            'business_branch_id' => $this->branch->id,
            'opening_cash' => 50000,
        ])->assertForbidden();

        $this->assertDatabaseCount('cash_drawer_sessions', 0);
    }

    public function test_operations_cannot_close_a_cash_drawer(): void
    {
        $this->actingAsRole('Executive');
        $session = CashDrawerSession::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'opened_by' => $this->currentUser->id,
            'opening_cash' => 50000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->actingAsRole('Operations');

        $this->postJson("/api/finances/cash-drawers/{$session->id}/close", [
            'counted_cash' => 49900,
        ])->assertForbidden();

        $this->assertDatabaseHas('cash_drawer_sessions', [
            'id' => $session->id,
            'status' => 'open',
        ]);
    }

    public function test_an_executive_can_open_a_cash_drawer(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/finances/cash-drawers/open', [
            'business_branch_id' => $this->branch->id,
            'opening_cash' => 50000,
        ])->assertCreated();
    }

    // -------------------------------------------------------- stock destruction

    private function stockProduct(): Product
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Write-off '.$this->currentUser->id,
            'status' => 'active',
        ]);

        return Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'product_category_id' => $category->id,
            'quantity' => 10,
            'cost_price' => 500,
            'status' => 'active',
        ]);
    }

    /**
     * InventoryService::writeOff() validates the reason and the quantity and never
     * asked which role was asking, so the HTTP entry point is the only place this can
     * be answered. The gate belongs here rather than in the service precisely because
     * the service is also reached from the stock-count and expiry-sweep paths that
     * Operations legitimately drives — ProductLossTest exercises those directly and
     * still passes.
     */
    public function test_operations_cannot_record_a_product_loss(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->stockProduct();

        $this->postJson('/api/product-losses', [
            'product_id' => $product->id,
            'type' => 'expired',
            'quantity' => 4,
            'reason' => 'Expired crate',
        ])->assertForbidden();

        $this->assertDatabaseCount('product_losses', 0);
        $this->assertSame(10, (int) $product->fresh()->quantity);
    }

    public function test_a_branch_manager_can_record_a_product_loss(): void
    {
        $this->actingAsRole('BranchManager');
        $product = $this->stockProduct();

        $this->postJson('/api/product-losses', [
            'product_id' => $product->id,
            'type' => 'expired',
            'quantity' => 4,
            'reason' => 'Expired crate',
        ])->assertCreated();

        $this->assertSame(6, (int) $product->fresh()->quantity);
    }

    public function test_operations_cannot_process_a_sale_return(): void
    {
        $this->actingAsRole('Operations');
        $saleItem = $this->soldSaleItem();

        $this->postJson('/api/returns/sale-returns', [
            'reason' => 'Wrong item handed over',
            'restock' => true,
            'items' => [
                ['sale_item_id' => $saleItem->id, 'quantity' => 1, 'condition' => 'resellable'],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('sale_returns', 0);
    }

    public function test_operations_cannot_process_a_purchase_return(): void
    {
        $this->actingAsRole('Operations');
        $purchase = $this->purchaseWithItems();
        $purchaseItem = $purchase->purchaseItems->first();

        $this->postJson('/api/returns/purchase-returns', [
            'supplier_id' => $purchase->supplier_id,
            'reason' => 'Damaged in transit',
            'restock' => true,
            'items' => [
                ['purchase_item_id' => $purchaseItem->id, 'quantity' => 1, 'condition' => 'resellable'],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('purchase_returns', 0);
    }

    /** A completed sale with one item on it, so a return has something to point at. */
    private function soldSaleItem(): SaleItem
    {
        $product = $this->stockProduct();

        $sale = Sale::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->currentUser->id,
            'total_amount' => 10000,
            'status' => 'completed',
        ]);

        $item = $sale->saleItems()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 5000,
            'subtotal' => 10000,
        ]);

        return $item;
    }

    // -------------------------------------------- trade documents and config

    public function test_operations_cannot_record_a_purchase(): void
    {
        $this->actingAsRole('Operations');
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Purchases',
            'status' => 'active',
        ]);
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'product_category_id' => $category->id,
        ]);
        $paymentMethod = $this->paymentMethod();

        $this->postJson('/api/purchases/branch-purchases', [
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'completed',
            'payment_status_id' => $paymentMethod->id,
            'currency' => 'UGX',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'cost_price' => 5000],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('purchases', 0);
    }

    /** Recording what was bought is Procurement's job, so this one must still work. */
    public function test_procurement_can_record_a_purchase(): void
    {
        $this->actingAsRole('Procurement');
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Purchases',
            'status' => 'active',
        ]);
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'product_category_id' => $category->id,
        ]);
        $paymentMethod = $this->paymentMethod();

        $this->postJson('/api/purchases/branch-purchases', [
            'business_branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'completed',
            'payment_status_id' => $paymentMethod->id,
            'currency' => 'UGX',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'cost_price' => 5000],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('purchases', 1);
    }

    /** Purchases settle against a payment method, so the receipt path needs a real one. */
    private function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::create([
            'business_id' => $this->business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);
    }

    private function saleOrderPayload(array $overrides = []): array
    {
        $product = $this->stockProduct();

        return array_merge([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ], $overrides);
    }

    public function test_operations_cannot_create_a_sale_order(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/sale-orders', $this->saleOrderPayload())->assertForbidden();

        $this->assertDatabaseCount('sale_orders', 0);
    }

    /**
     * A sale order pins a unit_price and an allocated_qty for a future delivery, and
     * this update can move it to 'approved' or 'delivered' — the sales-side twin of
     * approving a purchase order, which is already held to canManageBranch().
     */
    public function test_operations_cannot_edit_a_sale_order(): void
    {
        // The order has to exist before the refusal, so it is created as an Executive
        // and the session is then handed to Operations for the attempt to edit.
        $this->actingAsRole('Executive');
        $order = $this->saleOrderFor($this->saleOrderPayload());

        $this->actingAsRole('Operations');

        $this->putJson("/api/sale-orders/{$order->id}", ['status' => 'approved'])
            ->assertForbidden();

        $this->assertDatabaseHas('sale_orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_an_executive_can_create_a_sale_order(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/sale-orders', $this->saleOrderPayload())->assertCreated();
    }

    private function saleOrderFor(array $payload): SaleOrder
    {
        $id = $this->postJson('/api/sale-orders', $payload)->json('data.id');

        return SaleOrder::findOrFail($id);
    }

    public function test_operations_cannot_manage_printers(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/printers', [
            'business_branch_id' => $this->branch->id,
            'name' => 'Front counter',
            'type' => 'usb',
        ])->assertForbidden();

        $this->assertDatabaseCount('printers', 0);
    }

    public function test_operations_cannot_manage_reorder_rules(): void
    {
        $this->actingAsRole('Operations');
        $product = $this->stockProduct();

        $this->postJson('/api/reorder-rules', [
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'reorder_quantity' => 20,
        ])->assertForbidden();

        $this->assertDatabaseCount('reorder_rules', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'reorder_level' => $product->reorder_level,
        ]);
    }

    public function test_procurement_can_manage_reorder_rules(): void
    {
        $this->actingAsRole('Procurement');
        $product = $this->stockProduct();

        $this->postJson('/api/reorder-rules', [
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'reorder_quantity' => 20,
        ])->assertCreated();
    }

    public function test_operations_cannot_request_a_report_export(): void
    {
        $this->actingAsRole('Operations');

        $this->postJson('/api/report-exports', [
            'business_id' => $this->business->id,
            'user_id' => $this->currentUser->id,
            'report_type' => 'sales',
            'format' => 'csv',
        ])->assertForbidden();

        $this->assertDatabaseCount('report_exports', 0);
    }

    /**
     * Report exports follow canManageReports(), which is isElevated() — not
     * canManageBranch(). An export takes data out of the tenant scope entirely, so it
     * is a business-level read and a BranchManager does not get one. This is the one
     * case in this block where the gate is deliberately narrower than manager.
     */
    public function test_a_branch_manager_cannot_request_a_report_export(): void
    {
        $this->actingAsRole('BranchManager');

        $this->postJson('/api/report-exports', [
            'business_id' => $this->business->id,
            'user_id' => $this->currentUser->id,
            'report_type' => 'sales',
            'format' => 'csv',
        ])->assertForbidden();
    }

    public function test_an_executive_can_request_a_report_export(): void
    {
        $this->actingAsRole('Executive');

        $this->postJson('/api/report-exports', [
            'business_id' => $this->business->id,
            'user_id' => $this->currentUser->id,
            'report_type' => 'sales',
            'format' => 'csv',
        ])->assertCreated();
    }

    // -------------------------------------------------- hardened form requests

    /**
     * UpdateExpenseRequest::authorize() returned a bare true, which held only because
     * routes/api.php:94 puts `role` on the expenses group. Nothing in the request
     * said so, so the day that group is dropped it would authorize anything. The
     * middleware assertion below is what makes this a regression test rather than a
     * restatement: it fails if the group is ever dropped without the request being
     * hardened to match.
     */
    public function test_the_expenses_route_group_is_still_what_gates_expense_writes(): void
    {
        $route = collect(Route::getRoutes())
            ->first(fn ($route) => $route->uri() === 'api/expenses/branch-expenses/{expense}'
                && in_array('PUT', $route->methods(), true));

        $this->assertNotNull($route, 'The expense update route disappeared.');

        $this->assertContains(
            'role',
            $route->gatherMiddleware(),
            'The expenses group lost its role middleware. If that was deliberate, '
            .'UpdateExpenseRequest::authorize() now has to carry the check on its own.'
        );
    }

    public function test_operations_cannot_edit_an_expense(): void
    {
        $this->actingAsRole('Operations');
        $expense = $this->branchExpense();

        $this->putJson("/api/expenses/branch-expenses/{$expense->id}", [
            'amount' => 999999,
        ])->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 1000]);
    }

    private function branchExpense(): Expense
    {
        $category = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Rent',
            'status' => 'active',
        ]);

        return Expense::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'expense_category_id' => $category->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
    }
}
