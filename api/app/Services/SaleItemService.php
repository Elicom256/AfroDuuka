<?php

namespace App\Services;

use App\Models\BusinessBranchProduct;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;

class SaleItemService
{
   public function handleSaveSaleItem(array $validated, string $business_branch_id){
      $totalAmount = collect($validated["items"])->sum(fn($i) => $i["quantity"] * $i["unit_price"]);
      // create sale header
      $sale = Sale::create([
         'business_branch_id' => $business_branch_id,
          "total_amount" => $totalAmount,
          'note' => $validated["note"] ?? null,
           "status" => "completed" 
           ]);

      // create sale items
    foreach ($validated['items'] as $item) {
        SaleItem::create([
            'sale_id' => $sale->id,
            'business_branch_product_id' => $item['business_branch_product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['quantity'] * $item['unit_price'],
        ]);

        // decrement product stock
        BusinessBranchProduct::where('id', $item['business_branch_product_id'])
            ->decrement('quantity', $item['quantity']);
    }
    return $sale->load("saleItems");
   }
}