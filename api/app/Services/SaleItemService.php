<?php

namespace App\Services;

use App\Models\BusinessBranchProduct;
use App\Models\CashFlow;
use App\Models\CoreSettings\PaymentStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Exceptions;

use function PHPUnit\Framework\throwException;

class SaleItemService
{
    protected $cashFlowService;
    public function __construct(CashFlowService $cashFlowService)
    {
        $this->cashFlowService = $cashFlowService;
    }
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
      $product = BusinessBranchProduct::find($item['business_branch_product_id']);
      if($product->quantity < $item["quantity"]){
         throw new Exception("Products available are few to what you want to sale", 301);
      }
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
    $method = PaymentStatus::find($validated["method"])->value("method");
   //  to be removed
    SalePayment::create([
      "sale_id" => $sale->id,
      "method" => $method,
      "amount" => $totalAmount,
      "paymentStatus" => $validated["paymentStatus"],
    ]);
    // ==================== CREATE CASH FLOW ====================
    $this->cashFlowService->createCashFlowForSale($sale, $totalAmount, $validated, $method);
        // $this->createCashFlowForSale($sale, $totalAmount, $validated, $method);
    return $sale->load(["saleItems", "salePayment"]);
   }

  
}