<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TaxRate;
use App\Support\Money;

class TaxService
{
    /**
     * Resolve the effective tax rate applied to a product.
     *
     * A product without a tax category, or whose category has no active rates,
     * is not taxed. When a category carries multiple active rates the highest
     * one is treated as the effective rate.
     */
    public function effectiveRateForProduct(Product $product): ?TaxRate
    {
        if (! $product->tax_category_id) {
            return null;
        }

        $product->loadMissing('taxCategory.taxRates');

        $rate = $product->taxCategory?->taxRates
            ->where('is_active', true)
            ->sortByDesc(fn (TaxRate $r) => (float) $r->rate)
            ->first();

        return $rate;
    }

    /**
     * Calculate per-line transaction tax for a given product line.
     *
     * All arithmetic is performed in integer cents to eliminate floating-point
     * drift. Tax is rounded per sale line (half-up); sale-level tax totals are
     * the sum of the rounded line taxes.
     *
     * @return array{
     *     rate: float|null,
     *     is_tax_inclusive: bool,
     *     discounted_amount: float,
     *     taxable_amount: float,
     *     tax_amount: float,
     * }
     */
    public function calculateForProduct(
        Product $product,
        float $unitPrice,
        int $quantity,
        float $discountPerUnit = 0
    ): array {
        $rate = $this->effectiveRateForProduct($product);

        $unitPriceCents = Money::toCents($unitPrice);
        $discountPerUnitCents = Money::toCents($discountPerUnit);
        $discountedCents = Money::mul($unitPriceCents - $discountPerUnitCents, $quantity);

        if (! $rate) {
            return [
                'rate' => null,
                'is_tax_inclusive' => (bool) $product->is_tax_inclusive,
                'discounted_amount' => Money::fromCents($discountedCents),
                'taxable_amount' => Money::fromCents($discountedCents),
                'tax_amount' => 0.0,
            ];
        }

        $isTaxInclusive = (bool) $product->is_tax_inclusive;
        $rateCentsPerHundred = (int) round(((float) $rate->rate) * 10000);

        if ($isTaxInclusive) {
            $taxableCents = Money::roundHalfUp($discountedCents * 10000, 10000 + $rateCentsPerHundred);
            $taxCents = $discountedCents - $taxableCents;
        } else {
            $taxableCents = $discountedCents;
            $taxCents = Money::roundHalfUp($discountedCents * $rateCentsPerHundred, 10000);
        }

        return [
            'rate' => (float) $rate->rate,
            'is_tax_inclusive' => $isTaxInclusive,
            'discounted_amount' => Money::fromCents($discountedCents),
            'taxable_amount' => Money::fromCents($taxableCents),
            'tax_amount' => Money::fromCents($taxCents),
        ];
    }
}
