<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Support\Tenant\EffectiveBranchScope;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    protected TaxService $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function create(array $payload, ?int $branchId = null): Quotation
    {
        $user = Auth::user();
        $branchId = $branchId ?? $user->business_branch_id;

        $this->assertBranchInScope($branchId, $user);

        $lines = $this->resolveLines($payload['items']);
        [$discount, $subtotal, $tax, $total] = $this->totals($lines);

        return DB::transaction(function () use ($payload, $user, $branchId, $lines, $discount, $subtotal, $tax, $total) {
            $quotation = Quotation::create([
                'business_branch_id' => $branchId,
                'user_id'            => $user->id,
                'customer_id'        => $payload['customer_id'] ?? null,
                'quotation_number'   => $this->generateQuotationNumber($user),
                'status'             => 'draft',
                'valid_until'        => $payload['valid_until'] ?? null,
                'currency'           => $payload['currency'] ?? 'UGX',
                'subtotal'           => round($subtotal, 2),
                'tax_amount'         => round($tax, 2),
                'discount'           => round($discount, 2),
                'total_amount'       => round($total, 2),
                'notes'              => $payload['notes'] ?? null,
                'terms'              => $payload['terms'] ?? null,
            ]);

            $this->storeItems($quotation, $lines);

            return $quotation->load(['items.product', 'customer', 'user']);
        });
    }

    public function update(Quotation $quotation, array $payload): Quotation
    {
        if (! in_array($quotation->status, ['draft', 'sent'], true)) {
            throw new Exception('Only draft or sent quotations can be edited.', 409);
        }

        $lines = $this->resolveLines($payload['items']);
        [$discount, $subtotal, $tax, $total] = $this->totals($lines);

        return DB::transaction(function () use ($quotation, $payload, $lines, $discount, $subtotal, $tax, $total) {
            $quotation->update([
                'customer_id'  => $payload['customer_id'] ?? $quotation->customer_id,
                'valid_until'  => $payload['valid_until'] ?? $quotation->valid_until,
                'currency'     => $payload['currency'] ?? $quotation->currency,
                'subtotal'     => round($subtotal, 2),
                'tax_amount'   => round($tax, 2),
                'discount'     => round($discount, 2),
                'total_amount' => round($total, 2),
                'notes'        => $payload['notes'] ?? $quotation->notes,
                'terms'        => $payload['terms'] ?? $quotation->terms,
            ]);

            $quotation->items()->delete();
            $this->storeItems($quotation, $lines);

            return $quotation->load(['items.product', 'customer', 'user']);
        });
    }

    public function send(Quotation $quotation): Quotation
    {
        if ($quotation->status !== 'draft') {
            throw new Exception('Only draft quotations can be sent.', 409);
        }

        $quotation->update(['status' => 'sent']);

        return $quotation->load(['items.product', 'customer']);
    }

    public function cancel(Quotation $quotation): Quotation
    {
        if (! in_array($quotation->status, ['draft', 'sent'], true)) {
            throw new Exception('Only draft or sent quotations can be cancelled.', 409);
        }

        $quotation->update(['status' => 'cancelled']);

        return $quotation->load(['items.product', 'customer']);
    }

    /**
     * Client accepts the quote -> a SaleOrder is created (proposal.md step 2).
     * Inventory is only allocated/reserved, never decremented, and NO financial
     * record is written. Shipment (step 3) and Invoice (step 4) follow later.
     */
    public function accept(Quotation $quotation, ?int $branchId = null): SaleOrder
    {
        if (! in_array($quotation->status, ['draft', 'sent'], true)) {
            throw new Exception('Quotation is not in an acceptable state.', 409);
        }

        $user = Auth::user();
        $branchId = $branchId ?? $user->business_branch_id;

        $this->assertBranchInScope($branchId, $user);

        return DB::transaction(function () use ($quotation, $user, $branchId) {
            $order = SaleOrder::create([
                'business_branch_id' => $branchId,
                'user_id'            => $user->id,
                'customer_id'        => $quotation->customer_id,
                'quotation_id'       => $quotation->id,
                'order_number'       => $this->generateOrderNumber($user),
                'total_amount'       => $quotation->total_amount,
                'status'             => 'approved',
                'notes'              => ($quotation->notes ?? '') === ''
                    ? 'From quotation ' . $quotation->quotation_number
                    : $quotation->notes . ' (from ' . $quotation->quotation_number . ')',
            ]);

            $quotation->load('items');

            foreach ($quotation->items as $item) {
                SaleOrderItem::create([
                    'sale_order_id' => $order->id,
                    'product_id'    => $item->product_id,
                    'quantity'      => $item->quantity,
                    'allocated_qty' => $item->quantity,
                    'unit_price'    => $item->unit_price,
                    'subtotal'      => $item->subtotal,
                ]);
            }

            $quotation->update([
                'status'           => 'accepted',
                'accepted_order_id' => $order->id,
            ]);

            return $order->load(['items.product', 'customer']);
        });
    }

    protected function resolveLines(array $items): array
    {
        $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
        $products = Product::with('taxCategory.taxRates')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $errors = [];
        $lines = [];

        foreach ($items as $index => $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                $errors[] = 'Item #' . ($index + 1) . ': Product not found in this branch.';
                continue;
            }

            if ($product->status !== 'active') {
                $errors[] = "{$product->name}: Product is not available for sale.";
                continue;
            }

            if ($product->availableQuantity() < (int) $item['quantity']) {
                $errors[] = "{$product->name}: Only {$product->availableQuantity()} available, but {$item['quantity']} requested.";
                continue;
            }

            $tax = $this->taxService->calculateForProduct(
                $product,
                (float) $item['unit_price'],
                (int) $item['quantity'],
                (float) ($item['discount'] ?? 0)
            );

            $lines[] = [
                'product'          => $product,
                'quantity'         => (int) $item['quantity'],
                'unit_price'       => (float) $item['unit_price'],
                'discount'         => (float) ($item['discount'] ?? 0),
                'rate'             => $tax['rate'],
                'is_tax_inclusive' => $tax['is_tax_inclusive'],
                'taxable_amount'   => $tax['taxable_amount'],
                'tax_amount'       => $tax['tax_amount'],
                'discounted_amount' => $tax['discounted_amount'],
            ];
        }

        if (count($errors) > 0) {
            throw new Exception(implode('; ', $errors), 422);
        }

        return $lines;
    }

    protected function storeItems(Quotation $quotation, array $lines): void
    {
        foreach ($lines as $line) {
            $product = $line['product'];

            QuotationItem::create([
                'quotation_id'    => $quotation->id,
                'product_id'      => $product->id,
                'product_name'    => $product->name,
                'sku'             => $product->sku,
                'quantity'        => $line['quantity'],
                'unit_price'      => round($line['unit_price'], 2),
                'discount'        => round($line['discount'], 2),
                'tax_rate'        => $line['rate'],
                'is_tax_inclusive' => $line['is_tax_inclusive'],
                'taxable_amount'  => round($line['taxable_amount'], 2),
                'tax_amount'      => round($line['tax_amount'], 2),
                'subtotal'        => round($line['discounted_amount'], 2),
            ]);
        }
    }

    protected function totals(array $lines): array
    {
        $discount = 0;
        $subtotal = 0;
        $tax = 0;

        foreach ($lines as $line) {
            $discount += $line['discount'] * $line['quantity'];
            $subtotal += $line['taxable_amount'];
            $tax += $line['tax_amount'];
        }

        $total = round($subtotal + $tax, 2);

        return [round($discount, 2), round($subtotal, 2), round($tax, 2), $total];
    }

    protected function generateQuotationNumber($user): string
    {
        $last = Quotation::where('business_id', $user->business_id)
            ->whereDate('created_at', today())
            ->count();

        return 'QT-' . now()->format('Ymd') . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    protected function generateOrderNumber($user): string
    {
        $last = SaleOrder::where('business_id', $user->business_id)
            ->whereDate('created_at', today())
            ->count();

        return 'SO-' . now()->format('Ymd') . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    protected function assertBranchInScope(?int $branchId, $user): void
    {
        $resolved = EffectiveBranchScope::branchesFor($user);

        if ($resolved !== null && $branchId !== null && ! in_array($branchId, $resolved[1], true)) {
            throw new Exception('The selected business branch is outside your scope.', 403);
        }
    }
}