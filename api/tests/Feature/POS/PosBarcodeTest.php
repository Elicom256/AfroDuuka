<?php

namespace Tests\Feature\POS;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosBarcodeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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
        $role = Role::factory()->create(['business_id' => $this->business->id]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_resolves_exact_barcode(): void
    {
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Scannable Widget',
            'sku' => 'SCN-001',
            'barcode' => '890100000001',
            'quantity' => 7,
            'selling_price' => 3000,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/pos/products/by-barcode/890100000001")
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);

        $this->assertSame($product->id, $response->json('data.id'));
        $this->assertSame(7, $response->json('data.stock'));
    }

    public function test_returns_404_for_unknown_barcode(): void
    {
        $this->getJson('/api/pos/products/by-barcode/000000000000')
            ->assertStatus(404);
    }

    public function test_strips_whitespace_and_newline_from_scanner_input(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Newline Widget',
            'sku' => 'NLW-001',
            'barcode' => '777777777777',
            'quantity' => 3,
            'selling_price' => 1200,
            'status' => 'active',
        ]);

        $this->getJson("/api/pos/products/by-barcode/%0A777777777777")
            ->assertStatus(200)
            ->assertJsonPath('data.barcode', '777777777777');
    }

    public function test_branch_scoped_user_cannot_resolve_product_from_another_branch(): void
    {
        $otherBranch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);
        Product::factory()->create([
            'business_branch_id' => $otherBranch->id,
            'name' => 'Foreign Widget',
            'sku' => 'FRN-001',
            'barcode' => '555555555555',
            'quantity' => 4,
            'selling_price' => 2000,
            'status' => 'active',
        ]);

        $this->getJson('/api/pos/products/by-barcode/555555555555')
            ->assertStatus(404);
    }

    public function test_requires_barcode_value(): void
    {
        $this->getJson('/api/pos/products/by-barcode/%20')
            ->assertStatus(422);
    }
}