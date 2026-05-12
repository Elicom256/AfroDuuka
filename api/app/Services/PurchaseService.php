<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;

class PurchaseService
{
    public function savePurchase($validated)
    {
        $total_amount = collect($validated["items"])->sum(fn($i) => $i["cost_price"] * $i["quantity"]);
        $purchase = Purchase::create([
            "supplier_id" => $validated["supplier_id"],
            "total_amount" => $total_amount,
            "note" => $validated["note"] ?? null
        ]);
        foreach ($validated["items"] as $item) {
             PurchaseItem::create([
                "purchase_id" => $purchase->id,
                 "product_id" => $item["product_id"],
                 "quantity" => $item["quantity"],
                 "cost_price" => $item["cost_price"],
                 "subtotal" => $item["subtotal"]
             ]);
            //  increment the product by qty
              Product::where("id", $item["product_id"])
                     ->increment("quantity", $item["quantity"]);
        }
       
        return $purchase->load("purchaseItems");
    }
}