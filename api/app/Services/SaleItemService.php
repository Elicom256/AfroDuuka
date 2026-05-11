<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;

class SaleItemService
{
   public function handleSaveSaleItem(array $validated, string $businessId){
      $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["price"]);

      // create sale header
      $sale = Sale::create([
         'business_id' => $businessId,
          "total_amount" => $totalAmount,
           "status" => "completed" 
           ]);

      // create sale items
    foreach ($validated['items'] as $item) {
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'subtotal' => $item['quantity'] * $item['price'],
        ]);

        // decrement product stock
        Product::where('id', $item['product_id'])
            ->decrement('quantity', $item['quantity']);
    }
    return $sale->load("saleItems");
   }
}