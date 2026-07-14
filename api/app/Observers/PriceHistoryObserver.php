<?php

/*-----------------------------------------------------------------------------------
 * Observer: PriceHistoryObserver
 * -------------------------------
 * Hooks into the Product model's `updated` event.
 * Whenever a product's cost_price or price changes, a new PriceHistory row
 * is created automatically — never overwrites historical data.
 *---------------------------------------------------------------------------------*/

namespace App\Observers;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class PriceHistoryObserver
{
    /**
     * Handle the Product "updated" event.
     * Compares old vs new cost_price / price and records any changes.
     */
    public function updated(Product $product): void
    {
        $changes = [];

        // Check cost_price changes
        if ($product->isDirty('cost_price')) {
            $changes['old_cost_price'] = $product->getOriginal('cost_price');
            $changes['new_cost_price'] = $product->cost_price;
        }

        // Check sale price changes
        if ($product->isDirty('price')) {
            $changes['old_sale_price'] = $product->getOriginal('price');
            $changes['new_sale_price'] = $product->price;
        }

        // Only record if at least one price field changed
        if (empty($changes)) {
            return;
        }

        // change_reason can be passed as a temporary attribute on the product model
        $changeReason = $product->change_reason ?? null;

        PriceHistory::create(array_merge($changes, [
            'product_id'    => $product->id,
            'changed_by'    => Auth::id(),
            'change_reason' => $changeReason,
        ]));
    }
}
