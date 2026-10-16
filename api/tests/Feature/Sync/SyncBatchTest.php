<?php

namespace Tests\Feature\Sync;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\Todo;
use App\Models\User;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\Customer;
use App\Http\Requests\SyncBatchRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('sale_payments', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('cash_flows', 1);
        $this->assertDatabaseCount('receipts', 1);
        $this->assertSame(48, $this->product->fresh()->quantity);

        $this->postJson('/api/sync/batch', ['operations' => [$operation]])
            ->assertStatus(200)
            ->assertJsonPath('results.0.status', 'duplicate');

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('sale_payments', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('cash_flows', 1);
        $this->assertDatabaseCount('receipts', 1);
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

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
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
        $this->assertDatabaseCount('stock_movements', 1);
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
        $this->assertDatabaseCount('sales', 0);
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
        $this->assertDatabaseCount('sales', 0);
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
        $this->assertDatabaseCount('sales', 0);
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
        $this->assertDatabaseCount('stock_movements', 0);
    }

    private function assertDatabaseHasCustomerUser(string $email): int
    {
        $userId = (int) User::withoutGlobalScopes()->where('email', $email)->value('id');
        $this->assertGreaterThan(0, $userId);

        return $userId;
    }
}
