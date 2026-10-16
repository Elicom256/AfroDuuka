<?php

namespace Tests\Feature\Sync;

use App\Http\Requests\SyncBatchRequest;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\Todo;
use App\Models\User;
use App\Services\CashFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncBatchTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Business $business;

    protected BusinessBranch $branch;

    protected Product $product;

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
        $this->product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 50,
            'selling_price' => 10000,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_todo_operation_syncs_and_replay_returns_duplicate(): void
    {
        $operation = [
            'operation_id' => $this->faker->uuid(),
            'type' => 'todo',
            'payload' => ['title' => 'Offline todo'],
            'created_at' => now()->subMinutes(5)->toIso8601String(),
        ];

        $first = $this->postJson('/api/sync/batch', ['operations' => [$operation]]);
        $first->assertStatus(200)
            ->assertJsonPath('summary.synced', 1)
            ->assertJsonPath('results.0.status', 'synced')
            ->assertJsonPath('results.0.result.title', 'Offline todo');

        $this->assertDatabaseCount('todos', 1);
        $this->assertDatabaseHas('sync_operations', [
            'business_id' => $this->business->id,
            'operation_id' => $operation['operation_id'],
            'type' => 'todo',
            'status' => 'completed',
            'server_resource_type' => 'todo',
        ]);

        $replay = $this->postJson('/api/sync/batch', ['operations' => [$operation]]);
        $replay->assertStatus(200)
            ->assertJsonPath('summary.duplicate', 1)
            ->assertJsonPath('results.0.status', 'duplicate')
            ->assertJsonPath('results.0.server_id', $first->json('results.0.server_id'));

        $this->assertDatabaseCount('todos', 1);
    }

    public function test_one_bad_operation_does_not_block_the_batch(): void
    {
        $operations = [
            [
                'operation_id' => $this->faker->uuid(),
                'type' => 'todo',
                'payload' => ['title' => 'Kept todo'],
            ],
            [
                'operation_id' => $this->faker->uuid(),
                'type' => 'todo',
                'payload' => ['description' => 'No title'],
            ],
        ];

        $response = $this->postJson('/api/sync/batch', ['operations' => $operations]);

        $response->assertStatus(200)
            ->assertJsonPath('summary.synced', 1)
            ->assertJsonPath('summary.failed', 1)
            ->assertJsonPath('results.0.status', 'synced')
            ->assertJsonPath('results.1.status', 'failed')
            ->assertJsonPath('results.1.error.code', 'validation_error')
            ->assertJsonPath('results.1.error.details.title.0', 'The title field is required.');

        $this->assertDatabaseCount('todos', 1);
    }

    public function test_failed_operation_can_be_retried_after_correcting_its_payload(): void
    {
        $operationId = $this->faker->uuid();

        $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $operationId,
                'type' => 'todo',
                'payload' => ['description' => 'Missing title'],
            ]],
        ])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.0.error.code', 'validation_error');

        $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $operationId,
                'type' => 'todo',
                'payload' => ['title' => 'Corrected todo'],
            ]],
        ])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'synced')
            ->assertJsonPath('results.0.result.title', 'Corrected todo');

        $this->assertDatabaseHas('sync_operations', [
            'operation_id' => $operationId,
            'status' => 'completed',
            'attempts' => 2,
        ]);
        $this->assertDatabaseCount('todos', 1);
    }

    public function test_unsupported_type_fails_without_claiming_business_effects(): void
    {
        $response = $this->postJson('/api/sync/batch', [
            'operations' => [
                [
                    'operation_id' => $this->faker->uuid(),
                    'type' => 'driver',
                    'payload' => ['note' => 'ignored'],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('summary.failed', 1)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.0.error.code', 'unsupported_type');

        $this->assertDatabaseHas('sync_operations', [
            'operation_id' => $response->json('results.0.operation_id'),
            'status' => 'failed',
            'error_code' => 'unsupported_type',
        ]);
    }

    public function test_sync_batch_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/sync/batch', ['operations' => []])->assertStatus(401);
    }

    public function test_batch_size_is_capped(): void
    {
        $operations = array_fill(0, 51, [
            'operation_id' => $this->faker->uuid(),
            'type' => 'todo',
            'payload' => ['title' => 'x'],
        ]);

        $this->postJson('/api/sync/batch', ['operations' => $operations])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['operations']);
    }

    public function test_request_body_size_is_capped(): void
    {
        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'todo',
                'payload' => ['large_field' => str_repeat('x', SyncBatchRequest::MAX_REQUEST_BYTES)],
            ]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['operations']);
        $this->assertDatabaseCount('sync_operations', 0);
    }

    public function test_platform_operator_without_business_cannot_submit_sync_operations(): void
    {
        $siteAdminRole = Role::factory()->create([
            'business_id' => null,
            'name' => 'siteadmin',
        ]);
        $siteAdmin = User::factory()->create([
            'business_id' => null,
            'business_branch_id' => null,
            'role_id' => $siteAdminRole->id,
        ]);
        Sanctum::actingAs($siteAdmin);

        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'todo',
                'payload' => ['title' => 'Must remain tenant-bound'],
            ]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.0.error.code', 'forbidden');
        $this->assertDatabaseCount('sync_operations', 0);
        $this->assertDatabaseCount('todos', 0);
    }

    public function test_sale_operation_replays_without_duplicating_financial_or_stock_records(): void
    {
        $operation = [
            'operation_id' => $this->faker->uuid(),
            'type' => 'sale',
            'payload' => [
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                ]],
                'payments' => [['method' => 'cash', 'amount' => 20000]],
                'customer_id' => null,
            ],
        ];

        $first = $this->postJson('/api/sync/batch', ['operations' => [$operation]]);
        $first->assertStatus(200)
            ->assertJsonPath('results.0.status', 'synced');

        $saleId = (int) $first->json('results.0.server_id');
        $this->assertSaleEffectsAreSingle($saleId, $this->product->id);
        $this->assertSame(48, $this->product->fresh()->quantity);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'duplicate');

        $this->assertSaleEffectsAreSingle($saleId, $this->product->id);
        $this->assertSame(48, $this->product->fresh()->quantity);
    }

    public function test_sale_price_mismatch_is_a_conflict_without_side_effects(): void
    {
        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'sale',
                'payload' => [
                    'items' => [[
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 9000,
                    ]],
                    'payments' => [['method' => 'cash', 'amount' => 9000]],
                    'customer_id' => null,
                ],
            ]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'conflict')
            ->assertJsonPath('results.0.error.code', 'price_mismatch');

        $this->assertNoSaleEffectsForBranch($this->branch->id);
        $this->assertSame(50, $this->product->fresh()->quantity);
    }

    public function test_sale_with_insufficient_stock_is_a_conflict_without_side_effects(): void
    {
        $this->product->update(['quantity' => 1]);

        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'sale',
                'payload' => [
                    'items' => [[
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'unit_price' => 10000,
                    ]],
                    'payments' => [['method' => 'cash', 'amount' => 20000]],
                ],
            ]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'conflict')
            ->assertJsonPath('results.0.error.code', 'insufficient_stock');
        $this->assertNoSaleEffectsForBranch($this->branch->id);
        $this->assertSame(1, $this->product->fresh()->quantity);
    }

    public function test_sale_effects_roll_back_when_cash_flow_creation_fails(): void
    {
        $this->mock(CashFlowService::class, function ($mock) {
            $mock->shouldReceive('createCashFlowForSale')
                ->once()
                ->andThrow(new \RuntimeException('Simulated cash-flow failure'));
        });

        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'sale',
                'payload' => [
                    'items' => [[
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'unit_price' => 10000,
                    ]],
                    'payments' => [['method' => 'cash', 'amount' => 20000]],
                ],
            ]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.0.error.code', 'processing_error');
        $this->assertNoSaleEffectsForBranch($this->branch->id);
        $this->assertSame(50, $this->product->fresh()->quantity);
    }

    public function test_inventory_delta_is_audited_and_replay_does_not_apply_twice(): void
    {
        $operation = [
            'operation_id' => $this->faker->uuid(),
            'type' => 'inventory',
            'payload' => [
                'product_id' => $this->product->id,
                'quantity_delta' => -2,
                'reason' => 'damaged',
                'notes' => 'Broken packaging',
            ],
        ];

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'synced');

        $this->assertSame(48, $this->product->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'movement_key' => 'sync:'.$this->business->id.':'.$operation['operation_id'],
            'type' => 'adjustment',
            'quantity' => 2,
            'reason' => 'damaged',
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'duplicate');

        $this->assertSame(48, $this->product->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'movement_key' => 'sync:'.$this->business->id.':'.$operation['operation_id'],
        ]);
        $this->assertSame(1, DB::table('stock_movements')
            ->where('product_id', $this->product->id)
            ->where('movement_key', 'sync:'.$this->business->id.':'.$operation['operation_id'])
            ->count());
    }

    public function test_same_operation_uuid_is_independent_between_businesses(): void
    {
        $operationId = $this->faker->uuid();
        $operation = [
            'operation_id' => $operationId,
            'type' => 'todo',
            'payload' => ['title' => 'Tenant-owned task'],
        ];

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'synced');

        $otherBusiness = Business::factory()->create();
        $otherBranch = BusinessBranch::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);
        $otherRole = Role::factory()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Executive',
        ]);
        $otherUser = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'business_branch_id' => $otherBranch->id,
            'role_id' => $otherRole->id,
        ]);
        Sanctum::actingAs($otherUser);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'synced');

        $this->assertDatabaseCount('sync_operations', 2);
        $this->assertDatabaseHas('sync_operations', [
            'business_id' => $this->business->id,
            'operation_id' => $operationId,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('sync_operations', [
            'business_id' => $otherBusiness->id,
            'operation_id' => $operationId,
            'status' => 'completed',
        ]);
        $this->assertSame(1, Todo::withoutGlobalScopes()->where('business_id', $this->business->id)->count());
        $this->assertSame(1, Todo::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->count());
    }

    public function test_customer_operation_creates_once_and_returns_the_server_id_on_replay(): void
    {
        CustomersSettings::create([
            'business_id' => $this->business->id,
            'status' => 'enabled',
        ]);
        Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'customer',
        ]);

        $operation = [
            'operation_id' => $this->faker->uuid(),
            'type' => 'customer',
            'payload' => [
                'firstname' => 'Offline',
                'lastname' => 'Customer',
                'email' => 'offline.customer@example.test',
                'phone' => '0712345678',
            ],
        ];

        $first = $this->postJson('/api/sync/batch', ['operations' => [$operation]]);
        $first->assertStatus(200)
            ->assertJsonPath('results.0.status', 'synced');

        $serverId = $first->json('results.0.server_id');
        $this->assertNotEmpty($serverId);
        $this->assertDatabaseHas('customers', [
            'user_id' => $this->assertDatabaseHasCustomerUser('offline.customer@example.test'),
            'business_id' => $this->business->id,
        ]);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'duplicate')
            ->assertJsonPath('results.0.server_id', $serverId);

        $this->assertSame(1, Customer::withoutGlobalScopes()->where('business_id', $this->business->id)->count());
    }

    public function test_counterparty_cannot_sync_a_sale(): void
    {
        $counterpartyRole = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'customer',
        ]);
        $counterparty = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $counterpartyRole->id,
        ]);
        Sanctum::actingAs($counterparty);

        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'sale',
                'payload' => [
                    'items' => [[
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 10000,
                    ]],
                    'payments' => [['method' => 'cash', 'amount' => 10000]],
                ],
            ]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.0.error.code', 'forbidden');
        $this->assertNoSaleEffectsForBranch($this->branch->id);
        $this->assertSame(50, $this->product->fresh()->quantity);
    }

    public function test_foreign_tenant_product_and_customer_ids_are_rejected_for_sales(): void
    {
        $foreignBusiness = Business::factory()->create();
        $foreignBranch = BusinessBranch::factory()->create([
            'business_id' => $foreignBusiness->id,
        ]);
        $foreignProduct = Product::factory()->create([
            'business_branch_id' => $foreignBranch->id,
            'quantity' => 25,
            'selling_price' => 10000,
        ]);
        $foreignCustomerUser = User::factory()->create([
            'business_id' => $foreignBusiness->id,
            'business_branch_id' => $foreignBranch->id,
        ]);
        $foreignCustomer = Customer::factory()->create([
            'user_id' => $foreignCustomerUser->id,
            'business_id' => $foreignBusiness->id,
        ]);

        $response = $this->postJson('/api/sync/batch', [
            'operations' => [
                [
                    'operation_id' => $this->faker->uuid(),
                    'type' => 'sale',
                    'payload' => [
                        'items' => [[
                            'product_id' => $foreignProduct->id,
                            'quantity' => 1,
                            'unit_price' => 10000,
                        ]],
                        'payments' => [['method' => 'cash', 'amount' => 10000]],
                    ],
                ],
                [
                    'operation_id' => $this->faker->uuid(),
                    'type' => 'sale',
                    'payload' => [
                        'items' => [[
                            'product_id' => $this->product->id,
                            'quantity' => 1,
                            'unit_price' => 10000,
                        ]],
                        'payments' => [['method' => 'cash', 'amount' => 10000]],
                        'customer_id' => $foreignCustomer->id,
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.1.status', 'failed');
        $this->assertNoSaleEffectsForBranch($this->branch->id);
        $this->assertNoSaleEffectsForBranch($foreignBranch->id);
        $this->assertSame(25, $foreignProduct->fresh()->quantity);
    }

    public function test_sale_cannot_target_a_branch_from_another_business(): void
    {
        $foreignBusiness = Business::factory()->create();
        $foreignBranch = BusinessBranch::factory()->create([
            'business_id' => $foreignBusiness->id,
        ]);

        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'sale',
                'payload' => [
                    'business_branch_id' => $foreignBranch->id,
                    'items' => [[
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 10000,
                    ]],
                    'payments' => [['method' => 'cash', 'amount' => 10000]],
                ],
            ]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.0.error.code', 'validation_error');
        $this->assertNoSaleEffectsForBranch($this->branch->id);
        $this->assertSame(50, $this->product->fresh()->quantity);
    }

    public function test_inventory_sync_cannot_target_a_foreign_tenant_product(): void
    {
        $foreignBusiness = Business::factory()->create();
        $foreignBranch = BusinessBranch::factory()->create([
            'business_id' => $foreignBusiness->id,
        ]);
        $foreignProduct = Product::factory()->create([
            'business_branch_id' => $foreignBranch->id,
            'quantity' => 25,
        ]);

        $response = $this->postJson('/api/sync/batch', [
            'operations' => [[
                'operation_id' => $this->faker->uuid(),
                'type' => 'inventory',
                'payload' => [
                    'product_id' => $foreignProduct->id,
                    'quantity_delta' => 5,
                    'reason' => 'adjustment',
                ],
            ]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('results.0.status', 'failed')
            ->assertJsonPath('results.0.error.code', 'not_found');
        $this->assertSame(25, $foreignProduct->fresh()->quantity);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $foreignProduct->id]);
    }

    private function assertSaleEffectsAreSingle(int $saleId, int $productId): void
    {
        $this->assertSame(1, DB::table('sales')->where('id', $saleId)->count());
        $this->assertSame(1, DB::table('sale_items')->where('sale_id', $saleId)->count());
        $this->assertSame(1, DB::table('sale_payments')->where('sale_id', $saleId)->count());
        $this->assertSame(1, DB::table('stock_movements')
            ->where('product_id', $productId)
            ->where('reference_id', $saleId)
            ->count());
        $this->assertSame(1, DB::table('cash_flows')->where('sale_id', $saleId)->count());
        $this->assertSame(1, DB::table('receipts')->where('sale_id', $saleId)->count());
    }

    private function assertNoSaleEffectsForBranch(int $branchId): void
    {
        $this->assertSame(0, DB::table('sales')->where('business_branch_id', $branchId)->count());
        $this->assertSame(0, DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_branch_id', $branchId)
            ->count());
        $this->assertSame(0, DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.business_branch_id', $branchId)
            ->count());
        $this->assertSame(0, DB::table('stock_movements')->where('business_branch_id', $branchId)->count());
        $this->assertSame(0, DB::table('cash_flows')
            ->where('business_branch_id', $branchId)
            ->whereNotNull('sale_id')
            ->count());
        $this->assertSame(0, DB::table('receipts')->where('business_branch_id', $branchId)->count());
    }

    private function assertDatabaseHasCustomerUser(string $email): int
    {
        $userId = (int) User::withoutGlobalScopes()->where('email', $email)->value('id');
        $this->assertGreaterThan(0, $userId);

        return $userId;
    }
}
