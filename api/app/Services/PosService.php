<?php

namespace App\Services;

use App\Http\Resources\PosCustomerResource;
use App\Http\Resources\PosProductResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Support\Tenant\EffectiveBranchScope;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosService
{
    protected CashFlowService $cashFlowService;

    protected NotificationService $notificationService;

    protected TaxService $taxService;

    protected CustomerCreditService $customerCreditService;

    protected ReceiptNumberGenerator $receiptNumberGenerator;

    public function __construct(
        CashFlowService $cashFlowService,
        NotificationService $notificationService,
        TaxService $taxService,
        CustomerCreditService $customerCreditService,
        ReceiptNumberGenerator $receiptNumberGenerator
    ) {
        $this->cashFlowService = $cashFlowService;
        $this->notificationService = $notificationService;
        $this->taxService = $taxService;
        $this->customerCreditService = $customerCreditService;
        $this->receiptNumberGenerator = $receiptNumberGenerator;
    }

    public function searchProducts(string $query, int $limit = 20): array
    {
        $products = Product::with('productCategory')
            ->where(function ($q) use ($query) {
                $q->where('barcode', 'ILIKE', "{$query}%")
                    ->orWhere('sku', 'ILIKE', "{$query}%")
                    ->orWhere('name', 'ILIKE', "%{$query}%");
            })
            ->whereIn('status', ['active', 'inactive'])
            ->orderByRaw('CASE
                WHEN barcode LIKE ? THEN 1
                WHEN sku LIKE ? THEN 2
                ELSE 3
            END', ["{$query}%", "{$query}%"])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return PosProductResource::collection($products)->resolve();
    }

    /**
     * Resolve a product by an exact barcode scan (keyboard-wedge scanners send code + newline).
     * Tenant/branch scoped via the Product model's global L1 scopes.
     */
    public function scanByBarcode(string $barcode): array
    {
        $code = trim(preg_replace('/[\r\n\t\s]+/u', '', $barcode));

        $product = Product::with('productCategory')
            ->where('barcode', $code)
            ->whereIn('status', ['active', 'inactive'])
            ->first();

        return $product ? (new PosProductResource($product))->resolve() : [];
    }

    public function searchCustomers(string $query, int $limit = 20): array
    {
        $user = Auth::user();

        $customers = Customer::with('user')
            ->whereHas('user', function ($q) use ($user, $query) {
                $q->where('business_id', $user->business_id)
                    ->where(function ($sq) use ($query) {
                        $sq->where('firstname', 'ILIKE', "%{$query}%")
                            ->orWhere('lastname', 'ILIKE', "%{$query}%")
                            ->orWhere('phone', 'ILIKE', "%{$query}%")
                            ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$query}%"]);
                    });
            })
            ->orWhere('customer_code', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->get();

        return PosCustomerResource::collection($customers)->resolve();
    }

    public function validateCart(array $items): array
    {
        $errors = [];
        $requestedByProduct = [];
        $userBranchId = Auth::user()?->business_branch_id;

        foreach ($items as $index => $item) {
            $product = Product::where('id', $item['product_id'])
                ->first();

            if (! $product) {
                $errors[] = 'Item #'.($index + 1).': Product not found in this branch.';

                continue;
            }

            if ($userBranchId !== null && (int) $product->business_branch_id !== (int) $userBranchId) {
                $errors[] = 'Item #'.($index + 1).': Product not found in this branch.';

                continue;
            }

            if ($product->status !== 'active') {
                $errors[] = "{$product->name}: Product is not available for sale.";

                continue;
            }

            $requestedByProduct[$product->id] = ($requestedByProduct[$product->id] ?? 0) + (int) $item['quantity'];
        }

        foreach ($requestedByProduct as $productId => $requestedQuantity) {
            $product = Product::where('id', $productId)->first();

            if ($product && $product->quantity < $requestedQuantity) {
                $errors[] = "{$product->name}: Only {$product->quantity} available, but {$requestedQuantity} requested.";
            }
        }

        return $errors;
    }

    public function checkout(array $validated): Sale
    {
        $user = Auth::user();
        $branchId = $validated['business_branch_id'] ?? $user->business_branch_id;

        $resolved = $user ? EffectiveBranchScope::branchesFor($user) : null;
        if ($branchId && $resolved !== null) {
            [, $branchIds] = $resolved;
            if (! in_array($branchId, $branchIds, true)) {
                throw new Exception('Branch is not within your allowed scope', 403);
            }
        }

        $cartErrors = $this->validateCart($validated['items']);
        if (! empty($cartErrors)) {
            throw new Exception(implode('; ', $cartErrors), 422);
        }

        if (collect($validated['payments'])->contains(fn ($payment) => $payment['method'] === 'credit')
            && empty($validated['customer_id'])) {
            throw new Exception('A customer is required when using credit payment.', 422);
        }

        // Claimed before the sale transaction opens purely so the number is not
        // consumed by a checkout that turns out to have nothing to sell. nextval()
        // does not block, so this costs nothing in throughput.
        $receiptNumber = $this->receiptNumberGenerator->next(ReceiptNumberGenerator::POS_PREFIX);

        return DB::transaction(function () use ($validated, $user, $branchId, $receiptNumber) {
            $productIds = collect($validated['items'])->pluck('product_id')->unique()->values()->all();
            $products = Product::with('taxCategory.taxRates')
                ->whereIn('id', $productIds)
                ->where('business_branch_id', $branchId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (count($products) !== count($productIds)) {
                throw new Exception('One or more products are not available in this branch.', 422);
            }

            $requestedByProduct = [];
            foreach ($validated['items'] as $item) {
                $requestedByProduct[$item['product_id']] = ($requestedByProduct[$item['product_id']] ?? 0) + (int) $item['quantity'];
            }

            foreach ($requestedByProduct as $productId => $requestedQuantity) {
                $product = $products->get($productId);
                if ($product && $product->quantity < $requestedQuantity) {
                    throw new Exception("{$product->name}: Only {$product->quantity} available, but {$requestedQuantity} requested.", 422);
                }
            }

            $totalAmount = 0;
            $totalDiscount = 0;
            $totalSubtotal = 0;
            $totalTaxAmount = 0;

            if (isset($validated['sale_id'])) {
                $sale = Sale::where('id', $validated['sale_id'])
                    ->where('business_branch_id', $branchId)
                    ->where('user_id', $user->id)
                    ->where('status', 'held')
                    ->firstOrFail();
                $sale->update(['status' => 'completed', 'note' => $validated['note'] ?? $sale->note]);

                $totalAmount = (float) $sale->total_amount;
                $totalDiscount = (float) SaleItem::where('sale_id', $sale->id)
                    ->selectRaw('COALESCE(SUM(discount * quantity), 0) as total')
                    ->value('total');
            } else {
                foreach ($validated['items'] as $item) {
                    $product = $products->get($item['product_id']);
                    $tax = $this->taxService->calculateForProduct(
                        $product,
                        (float) $item['unit_price'],
                        (int) $item['quantity'],
                        (float) ($item['discount'] ?? 0)
                    );
                    $totalSubtotal += $tax['taxable_amount'];
                    $totalTaxAmount += $tax['tax_amount'];
                }

                $totalAmount = round($totalSubtotal + $totalTaxAmount, 2);
                $totalSubtotal = round($totalSubtotal, 2);
                $totalTaxAmount = round($totalTaxAmount, 2);

                $sale = Sale::create([
                    'business_branch_id' => $branchId,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'subtotal' => $totalSubtotal,
                    'tax_amount' => $totalTaxAmount,
                    'total_amount' => $totalAmount,
                    'note' => $validated['note'] ?? null,
                    'status' => 'completed',
                ]);

                foreach ($validated['items'] as $item) {
                    $product = $products->get($item['product_id']);
                    $lineDiscount = ($item['discount'] ?? 0) * $item['quantity'];
                    $subtotal = ($item['quantity'] * $item['unit_price']) - $lineDiscount;
                    $tax = $this->taxService->calculateForProduct(
                        $product,
                        (float) $item['unit_price'],
                        (int) $item['quantity'],
                        (float) ($item['discount'] ?? 0)
                    );

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount' => $item['discount'] ?? 0,
                        'tax_rate' => $tax['rate'],
                        'is_tax_inclusive' => $tax['is_tax_inclusive'],
                        'taxable_amount' => $tax['taxable_amount'],
                        'tax_amount' => $tax['tax_amount'],
                        'subtotal' => $subtotal,
                    ]);

                    $product->decrement('quantity', $item['quantity']);
                    $product->update(['last_sold_at' => now()]);

                    StockMovement::updateOrCreate(
                        [
                            'movement_key' => $this->stockMovementKey('out', (int) $item['product_id'], (int) $sale->id, Sale::class, (int) $item['quantity']),
                        ],
                        [
                            'business_id' => $user->business_id,
                            'business_branch_id' => $branchId,
                            'product_id' => $item['product_id'],
                            'type' => 'out',
                            'quantity' => $item['quantity'],
                            'reference_type' => Sale::class,
                            'reference_id' => $sale->id,
                            'notes' => 'POS sale',
                        ]
                    );

                    if ($product->quantity <= $product->reorder_level) {
                        $this->notificationService->lowStockAlert(
                            $user,
                            $product->name ?? $product->id,
                            $product->quantity,
                            $product->reorder_level,
                            $product->id
                        );
                    }
                }
            }

            $totalPaid = 0;
            $creditAmount = 0;
            foreach ($validated['payments'] as $payment) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'paymentStatus' => 'paid',
                ]);
                $totalPaid += $payment['amount'];
                if ($payment['method'] === 'credit') {
                    $creditAmount += $payment['amount'];
                }
            }

            $netTotal = $totalAmount;
            $changeGiven = max(0, $totalPaid - $totalAmount);

            if ($creditAmount > 0) {
                $this->customerCreditService->recordCharge($user, $sale, $creditAmount);
            }

            $customer = isset($validated['customer_id'])
                ? Customer::with('user')->find($validated['customer_id'])?->user
                : null;
            $customerName = $customer ? trim($customer->firstname.' '.$customer->lastname) : 'Walk-in Customer';

            $this->cashFlowService->createCashFlowForSale($sale, $netTotal, [
                'transaction_code' => 'CF-POS-'.str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                'currency' => $validated['currency'] ?? 'UGX',
                'payment_status_id' => 1,
                'reference' => null,
            ]);

            $this->notificationService->newSaleRecorded($user, number_format($netTotal), $customerName, $sale->id);

            $this->createPosReceipt($sale, $validated, $totalPaid, $changeGiven, $receiptNumber);

            return $sale->load(['saleItems.product', 'receipt.items']);
        });
    }

    protected function stockMovementKey(string $type, int $productId, int $referenceId, string $referenceType, int $quantity): string
    {
        return md5($referenceType.':'.$referenceId.':'.$productId.':'.$type.':'.$quantity);
    }

    protected function createPosReceipt(Sale $sale, array $validated, float $amountPaid, float $changeGiven, string $receiptNumber): Receipt
    {
        $user = Auth::user();
        $discountTotal = (float) SaleItem::where('sale_id', $sale->id)
            ->selectRaw('COALESCE(SUM(discount * quantity), 0) as total')
            ->value('total');
        $subtotal = (float) ($sale->subtotal ?? $sale->total_amount);
        $tax = (float) ($sale->tax_amount ?? 0);
        $total = (float) $sale->total_amount;
        $paymentMethod = collect($validated['payments'])->pluck('method')->implode(', ');

        $receipt = Receipt::create([
            'receipt_number' => $receiptNumber,
            'customer_id' => $sale->customer_id,
            'user_id' => $user->id,
            'business_id' => $user->business_id,
            'business_branch_id' => $sale->business_branch_id,
            'sale_id' => $sale->id,
            'subtotal' => $subtotal,
            'discount' => $discountTotal,
            'tax' => $tax,
            'total' => $total,
            'amount_paid' => $amountPaid,
            'change_given' => $changeGiven,
            'payment_method' => $paymentMethod,
            'status' => 'completed',
            'notes' => $sale->note,
        ]);

        $saleItems = SaleItem::with('product')->where('sale_id', $sale->id)->get();

        foreach ($saleItems as $item) {
            ReceiptItem::create([
                'receipt_id' => $receipt->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Unknown',
                'sku' => $item->product?->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount ?? 0,
                'line_total' => $item->subtotal,
            ]);
        }

        return $receipt->load('items');
    }

    public function holdSale(array $items, ?int $customerId, ?string $notes, ?int $businessBranchId = null): Sale
    {
        $user = Auth::user();
        $branchId = $businessBranchId ?? $user->business_branch_id;

        $resolved = EffectiveBranchScope::branchesFor($user);
        if ($resolved !== null && $branchId !== null && ! in_array($branchId, $resolved[1], true)) {
            throw new Exception('The selected business branch is outside your scope.');
        }

        return DB::transaction(function () use ($items, $customerId, $notes, $user, $branchId) {
            $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
            $products = Product::with('taxCategory.taxRates')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $totalSubtotal = 0;
            $totalTaxAmount = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $tax = $this->taxService->calculateForProduct(
                    $product,
                    (float) $item['unit_price'],
                    (int) $item['quantity'],
                    (float) ($item['discount'] ?? 0)
                );
                $totalSubtotal += $tax['taxable_amount'];
                $totalTaxAmount += $tax['tax_amount'];
            }

            $totalAmount = round($totalSubtotal + $totalTaxAmount, 2);
            $totalSubtotal = round($totalSubtotal, 2);
            $totalTaxAmount = round($totalTaxAmount, 2);

            $sale = Sale::create([
                'business_branch_id' => $branchId,
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'subtotal' => $totalSubtotal,
                'tax_amount' => $totalTaxAmount,
                'total_amount' => $totalAmount,
                'note' => $notes,
                'status' => 'held',
            ]);

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $lineDiscount = ($item['discount'] ?? 0) * $item['quantity'];
                $subtotal = ($item['quantity'] * $item['unit_price']) - $lineDiscount;
                $tax = $this->taxService->calculateForProduct(
                    $product,
                    (float) $item['unit_price'],
                    (int) $item['quantity'],
                    (float) ($item['discount'] ?? 0)
                );

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax_rate' => $tax['rate'],
                    'is_tax_inclusive' => $tax['is_tax_inclusive'],
                    'taxable_amount' => $tax['taxable_amount'],
                    'tax_amount' => $tax['tax_amount'],
                    'subtotal' => $subtotal,
                ]);
            }

            return $sale->load(['saleItems.product', 'customer.user']);
        });
    }

    public function getHeldSales(): array
    {
        $user = Auth::user();

        return Sale::where('user_id', $user->id)
            ->where('status', 'held')
            ->with('customer.user', 'saleItems.product')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function resumeHeldSale(int $id): Sale
    {
        $user = Auth::user();

        return Sale::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'held')
            ->with('saleItems.product', 'customer.user')
            ->firstOrFail();
    }

    public function deleteHeldSale(int $id): void
    {
        $user = Auth::user();

        $sale = Sale::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'held')
            ->firstOrFail();

        $sale->delete();
    }
}
