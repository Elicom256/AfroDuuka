<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessBranchProduct;
use App\Models\CoreSettings\PaymentStatus;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

class PurchaseService
{
     protected $cashFlowService;
    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
    }
    public function savePurchase($validated)
    {
        $notificationService = app(NotificationService::class);
        $user = Auth::user();
        $total_amount = collect($validated["items"])->sum(fn($i) => $i["cost_price"] * $i["quantity"]);
        $purchase = Purchase::create([
            "supplier_id" => $validated["supplier_id"],
            "business_branch_id" => $validated["business_branch_id"],
            "total_amount" => $total_amount,
            "note" => $validated["note"] ?? null
        ]);
        // $method = PaymentStatus::find($validated["payment_status_id"])->value("method");
        foreach ($validated["items"] as $item) {
             PurchaseItem::create([
                "purchase_id" => $purchase->id,
                 "business_branch_product_id" => $item["business_branch_product_id"],
                 "quantity" => $item["quantity"],
                 "cost_price" => $item["cost_price"],
                 "subtotal" => $item["cost_price"] * $item["quantity"]
             ]);
             $businessProduct = BusinessBranchProduct::find($item["business_branch_product_id"]);
             if($businessProduct){
                $price = $item["cost_price"] * (1 + $businessProduct->markup_percentage) ?? $businessProduct->price;
                $businessProduct->increment("quantity", $item["quantity"]);
                $businessProduct->update([
                    "cost_price" => $item["cost_price"],
                    "price" => $price,
                ]);  
             }
        }
        $supplier = Supplier::find($purchase->supplier_id);
         // ==================== CREATE CASH FLOW ====================
    $this->cashFlowService->createCashFlowForPurchase($purchase, $total_amount, $validated);
         // ==================== CREATE PURCHASE NOTIFICATION  ====================
    $notificationService->newPurchaseRecorded($user, $supplier->company_name, number_format($total_amount));
       
        return $purchase->load("purchaseItems");
    }
}