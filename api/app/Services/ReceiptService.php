<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceiptService
{
    public function __construct(
        protected ReceiptNumberGenerator $receiptNumberGenerator
    ) {}

    public function createReceiptForSale(Sale $sale, array $validated): Receipt
    {
        $user = Auth::user();

        $receiptNumber = $this->receiptNumberGenerator->next(ReceiptNumberGenerator::RECEIPT_PREFIX);

        return DB::transaction(function () use ($sale, $validated, $receiptNumber, $user) {
            $subtotal = (float) $sale->subtotal ?? $sale->total_amount;
            $tax = (float) $sale->tax_amount ?? 0;
            $total = (float) $sale->total_amount;
            $amountPaid = $validated['amount_paid'] ?? $total;
            $changeGiven = $validated['change_given'] ?? max(0, $amountPaid - $total);

            $receipt = Receipt::create([
                'receipt_number' => $receiptNumber,
                'customer_id' => $sale->customer_id,
                'user_id' => $user->id,
                'business_id' => $user->business_id,
                'business_branch_id' => $sale->business_branch_id,
                'sale_id' => $sale->id,
                'subtotal' => $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'tax' => $tax,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'change_given' => $changeGiven,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'status' => 'completed',
                'notes' => $sale->note,
            ]);

            $saleItems = SaleItem::with('product')
                ->where('sale_id', $sale->id)
                ->get();

            foreach ($saleItems as $item) {
                ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? 'Unknown Product',
                    'sku' => $item->product?->sku ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount ?? 0,
                    'line_total' => $item->subtotal,
                ]);
            }

            return $receipt->load(['items', 'user', 'customer', 'sale']);
        });
    }
}
