<?php

namespace Tests\Feature\Tax;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Product;
use App\Models\Role;
use App\Models\TaxCategory;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaxRoundingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected TaxCategory $category;

    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();

        $business = Business::factory()->create();
        $branch = BusinessBranch::factory()->create(['business_id' => $business->id]);
        $role = Role::factory()->create(['business_id' => $business->id]);
        $this->user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch->id,
            'role_id' => $role->id,
        ]);

        $this->category = TaxCategory::factory()->create([
            'business_branch_id' => $branch->id,
            'name' => 'VAT',
        ]);

        $this->taxService = new TaxService;

        Sanctum::actingAs($this->user);
    }

    private function product(float $price, bool $taxInclusive = false, ?float $rate = 0.18): Product
    {
        TaxRate::factory()->create([
            'tax_category_id' => $this->category->id,
            'name' => 'VAT '.($rate * 100).'%',
            'rate' => $rate,
            'is_active' => true,
        ]);

        return Product::factory()->create([
            'business_branch_id' => $this->user->business_branch_id,
            'tax_category_id' => $this->category->id,
            'is_tax_inclusive' => $taxInclusive,
            'selling_price' => $price,
            'quantity' => 50,
            'status' => 'active',
        ]);
    }

    public function test_tax_exclusive_rounds_half_up_per_line(): void
    {
        $product = $this->product(33.33);

        $result = $this->taxService->calculateForProduct($product, 33.33, 3);

        $this->assertEquals(1800, round($result['tax_amount'] * 100));
        $this->assertEquals(9999, round($result['taxable_amount'] * 100));
    }

    public function test_tax_inclusive_extraction_is_exact(): void
    {
        $product = $this->product(1180.00, true);

        $result = $this->taxService->calculateForProduct($product, 1180.00, 1);

        $chargedCents = round($result['discounted_amount'] * 100);
        $taxableCents = round($result['taxable_amount'] * 100);
        $taxCents = round($result['tax_amount'] * 100);

        $this->assertEquals($chargedCents, $taxableCents + $taxCents);
    }

    public function test_tax_inclusive_extraction_exact_across_rates(): void
    {
        foreach ([0.05, 0.18, 0.25] as $rate) {
            $product = $this->product(1000.00 + ($rate * 1000), true, $rate);

            $result = $this->taxService->calculateForProduct($product, 1000.00 + ($rate * 1000), 1);

            $chargedCents = round($result['discounted_amount'] * 100);
            $taxableCents = round($result['taxable_amount'] * 100);
            $taxCents = round($result['tax_amount'] * 100);

            $this->assertEquals($chargedCents, $taxableCents + $taxCents, "Rate {$rate} failed");
        }
    }

    public function test_discount_applies_before_tax(): void
    {
        $product = $this->product(1000.00);

        $result = $this->taxService->calculateForProduct($product, 1000.00, 2, 100.00);

        $this->assertEquals(1800.00, $result['discounted_amount']);
        $this->assertEquals(324.00, $result['tax_amount']);
        $this->assertEquals(1800.00, $result['taxable_amount']);
    }

    public function test_multi_line_sale_tax_is_sum_of_rounded_line_taxes(): void
    {
        $product = $this->product(33.33);

        $line1 = $this->taxService->calculateForProduct($product, 33.33, 3);
        $line2 = $this->taxService->calculateForProduct($product, 33.33, 2);

        $totalTaxCents = round($line1['tax_amount'] * 100) + round($line2['tax_amount'] * 100);
        $aggregateCents = round($line1['taxable_amount'] * $line2['taxable_amount'] / max($line1['taxable_amount'], 1) * 0.18 * 100);

        $this->assertGreaterThan(0, $totalTaxCents);
        $this->assertEquals(3000, $totalTaxCents);
    }
}
