<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TaxRate;

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
        if (!$product->tax_category_id) {
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
     * The unit price mirrors what the POS client sends (typically the product's
     * selling price). When the product price is tax-inclusive, the tax is
     * derived from the portion embedded in the selling price; otherwise tax is
     * added on top of the discounted line amount.
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
        $discountedAmount = round(($quantity * $unitPrice) - ($discountPerUnit * $quantity), 2);

        if (!$rate) {
            return [
                'rate'              => null,
                'is_tax_inclusive'  => (bool) $product->is_tax_inclusive,
                'discounted_amount' => $discountedAmount,
                'taxable_amount'    => $discountedAmount,
                'tax_amount'        => 0,
            ];
        }

        $rateValue = (float) $rate->rate;
        $isTaxInclusive = (bool) $product->is_tax_inclusive;

        if ($isTaxInclusive) {
            $taxableAmount = round($discountedAmount / (1 + $rateValue), 2);
            $taxAmount = round($discountedAmount - $taxableAmount, 2);
        } else {
            $taxableAmount = $discountedAmount;
            $taxAmount = round($discountedAmount * $rateValue, 2);
        }

        return [
            'rate'              => $rateValue,
            'is_tax_inclusive'  => $isTaxInclusive,
            'discounted_amount' => $discountedAmount,
            'taxable_amount'    => $taxableAmount,
            'tax_amount'        => $taxAmount,
        ];
    }
}