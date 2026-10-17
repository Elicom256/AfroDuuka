<?php

namespace Tests\Feature\Sync;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\CoreSettings\SuppliersSettings;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * One focused pass per sync handler added for user-owned CRUD.
 *
 * Purchase is the money path called out in the offline brief, so it also covers
 * duplicate replay, a foreign product, and a payload that must not leave a row.
 */
class SyncDomainHandlersTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Business $business;

    private BusinessBranch $branch;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);
        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'Executive']);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);
        $this->product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 50,
            'cost_price' => 5000,
            'selling_price' => 10000,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_purchase_syncs_replays_and_rejects_another_tenants_product(): void
    {
        $payment = PaymentMethod::create([
            'business_id' => $this->business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        $operation = $this->operation('purchase', [
            'action' => 'create',
            'body' => [
                'business_branch_id' => $this->branch->id,
                'status' => 'pending',
                'payment_status_id' => $payment->id,
                'currency' => 'UGX',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 2, 'cost_price' => 5000],
                ],
            ],
        ]);

        $first = $this->postJson('/api/sync/batch', ['operations' => [$operation]]);
        $first->assertOk()
            ->assertJsonPath('results.0.status', 'synced')
            ->assertJsonPath('summary.synced', 1);
        $this->assertDatabaseCount('purchases', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate')
            ->assertJsonPath('results.0.server_id', $first->json('results.0.server_id'));
        $this->assertDatabaseCount('purchases', 1);

        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);
        $otherProduct = Product::factory()->create([
            'business_branch_id' => $otherBranch->id,
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 2000,
            'status' => 'active',
        ]);

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('purchase', [
                'action' => 'create',
                'body' => [
                    'business_branch_id' => $this->branch->id,
                    'status' => 'pending',
                    'payment_status_id' => $payment->id,
                    'currency' => 'UGX',
                    'items' => [
                        ['product_id' => $otherProduct->id, 'quantity' => 1, 'cost_price' => 1000],
                    ],
                ],
            ]),
        ]]);

        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_a_purchase_payload_that_fails_validation_leaves_no_row(): void
    {
        $response = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('purchase', ['action' => 'create', 'body' => ['items' => []]]),
        ]]);

        $response->assertOk();
        $this->assertNotSame('synced', $response->json('results.0.status'));
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_promotion_syncs_and_replays(): void
    {
        $operation = $this->operation('promotion', [
            'action' => 'create',
            'body' => [
                'title' => 'Market day',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addWeek()->toDateString(),
            ],
        ]);

        $first = $this->postJson('/api/sync/batch', ['operations' => [$operation]]);
        $first->assertOk()->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('promotions', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('promotions', 1);
    }

    public function test_product_create_syncs_replays_and_a_bad_body_adds_nothing(): void
    {
        $before = Product::query()->count();
        $operation = $this->operation('product', [
            'action' => 'create',
            'body' => [
                'name' => 'Offline rice',
                'quantity' => 4,
                'cost_price' => 2000,
                'selling_price' => 3000,
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseHas('products', ['name' => 'Offline rice', 'business_branch_id' => $this->branch->id]);
        $this->assertSame($before + 1, Product::query()->count());

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertSame($before + 1, Product::query()->count());

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('product', ['action' => 'create', 'body' => []]),
        ]]);
        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertSame($before + 1, Product::query()->count());
    }

    public function test_product_update_cannot_rename_another_tenants_product(): void
    {
        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $otherBusiness->id]);
        $foreign = Product::factory()->create([
            'business_branch_id' => $otherBranch->id,
            'name' => 'Theirs',
            'quantity' => 1,
            'cost_price' => 100,
            'selling_price' => 200,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('product', [
                'action' => 'update',
                'id' => $foreign->id,
                'body' => ['name' => 'Stolen'],
            ]),
        ]]);

        $response->assertOk();
        $this->assertNotSame('synced', $response->json('results.0.status'));
        $this->assertDatabaseHas('products', ['id' => $foreign->id, 'name' => 'Theirs']);
    }

    public function test_order_syncs_and_replays(): void
    {
        $operation = $this->operation('order', [
            'action' => 'create',
            'body' => [
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10000],
                ],
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('sale_orders', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('sale_orders', 1);
    }

    public function test_quotation_syncs_replays_and_a_bad_body_adds_nothing(): void
    {
        $operation = $this->operation('quotation', [
            'action' => 'create',
            'body' => [
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10000],
                ],
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('quotations', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('quotations', 1);

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('quotation', ['action' => 'create', 'body' => []]),
        ]]);
        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertDatabaseCount('quotations', 1);
    }

    public function test_expense_syncs(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Transport',
            'business_id' => $this->business->id,
            'created_by' => $this->user->id,
        ]);

        $operation = $this->operation('expense', [
            'action' => 'create',
            'body' => [
                'expense_category_id' => $category->id,
                'amount' => 15000,
                'payment_date' => now()->toDateString(),
                'description' => 'Boda to the market',
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('expenses', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('expenses', 1);

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('expense', ['action' => 'create', 'body' => []]),
        ]]);
        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_coupon_syncs(): void
    {
        $operation = $this->operation('coupon', [
            'action' => 'create',
            'body' => [
                'discount_type' => 'fixed',
                'discount_value' => 500,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addMonth()->toDateString(),
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('coupons', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('coupons', 1);

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('coupon', ['action' => 'create', 'body' => []]),
        ]]);
        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertDatabaseCount('coupons', 1);
    }

    public function test_supplier_syncs(): void
    {
        SuppliersSettings::create([
            'business_id' => $this->business->id,
            'status' => 'enabled',
        ]);
        Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'supplier',
        ]);

        $operation = $this->operation('supplier', [
            'action' => 'create',
            'body' => [
                'firstname' => 'Amina',
                'lastname' => 'Kato',
                'email' => 'amina@example.com',
                'phone' => '0700000001',
                'company_name' => 'Kato Supplies',
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('suppliers', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('suppliers', 1);

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('supplier', ['action' => 'create', 'body' => []]),
        ]]);
        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertDatabaseCount('suppliers', 1);
    }

    public function test_worker_syncs(): void
    {
        Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Operations',
        ]);

        $operation = $this->operation('worker', [
            'action' => 'create',
            'body' => [
                'firstname' => 'Peter',
                'lastname' => 'Okello',
                'email' => 'peter@example.com',
                'phone' => '0700000002',
                'position' => 'Cashier',
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('workers', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('workers', 1);

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('worker', ['action' => 'create', 'body' => []]),
        ]]);
        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertDatabaseCount('workers', 1);
    }

    public function test_sale_return_syncs_and_a_bad_payload_leaves_no_row(): void
    {
        $sale = Sale::create([
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'total_amount' => 10000,
            'status' => 'completed',
        ]);
        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        $operation = $this->operation('sale_return', [
            'action' => 'create',
            'body' => [
                'items' => [
                    ['sale_item_id' => $item->id, 'quantity' => 1],
                ],
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('sale_returns', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('sale_returns', 1);

        $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('sale_return', ['action' => 'create', 'body' => []]),
        ]])->assertOk();
        $this->assertDatabaseCount('sale_returns', 1);
    }

    public function test_purchase_return_syncs(): void
    {
        $payment = PaymentMethod::create([
            'business_id' => $this->business->id,
            'method' => 'cash',
            'status' => 'enabled',
        ]);

        $created = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('purchase', [
                'action' => 'create',
                'body' => [
                    'business_branch_id' => $this->branch->id,
                    'status' => 'pending',
                    'payment_status_id' => $payment->id,
                    'currency' => 'UGX',
                    'items' => [
                        ['product_id' => $this->product->id, 'quantity' => 2, 'cost_price' => 5000],
                    ],
                ],
            ]),
        ]]);
        $created->assertOk()->assertJsonPath('results.0.status', 'synced');

        $purchase = Purchase::query()->with('purchaseItems')->first();
        $this->assertNotNull($purchase);

        $operation = $this->operation('purchase_return', [
            'action' => 'create',
            'body' => [
                'items' => [
                    ['purchase_item_id' => $purchase->purchaseItems->first()->id, 'quantity' => 1],
                ],
            ],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'synced');
        $this->assertDatabaseCount('purchase_returns', 1);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');
        $this->assertDatabaseCount('purchase_returns', 1);

        $rejected = $this->postJson('/api/sync/batch', ['operations' => [
            $this->operation('purchase_return', ['action' => 'create', 'body' => []]),
        ]]);
        $rejected->assertOk();
        $this->assertNotSame('synced', $rejected->json('results.0.status'));
        $this->assertDatabaseCount('purchase_returns', 1);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function operation(string $type, array $payload): array
    {
        return [
            'operation_id' => $this->faker->uuid(),
            'type' => $type,
            'payload' => $payload,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
