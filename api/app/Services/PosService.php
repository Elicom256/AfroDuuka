<?php

namespace App\Services;

use App\Http\Resources\PosCustomerResource;
use App\Http\Resources\PosProductResource;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosService
{
    protected CashFlowService $cashFlowService;
    protected NotificationService $notificationService;

    public function __construct(CashFlowService $cashFlowService, NotificationService $notificationService)
    {
        $this->cashFlowService = $cashFlowService;
        $this->notificationService = $notificationService;
    }

    public function searchProducts(string $query, int $limit = 20): array
    {
        $user = Auth::user();
        $branchId = $user->business_branch_id;

        $products = Product::with('productCategory')
            ->where('business_branch_id', $branchId)
            ->where(function ($q) use ($query) {
                $q->where('barcode', 'ILIKE', "{$query}%")
                  ->orWhere('sku', 'ILIKE', "{$query}%")
                  ->orWhere('name', 'ILIKE', "%{$query}%");
            })
            ->whereIn('status', ['active', 'inactive'])
            ->orderByRaw("CASE
                WHEN barcode LIKE ? THEN 1
                WHEN sku LIKE ? THEN 2
                ELSE 3
            END", ["{$query}%", "{$query}%"])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return PosProductResource::collection($products)->resolve();
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
        $user = Auth::user();
        $branchId = $user->business_branch_id;
        $errors = [];

        foreach ($items as $index => $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('business_branch_id', $branchId)
                ->first();

            if (!$product) {
                $errors[] = "Item #" . ($index + 1) . ": Product not found in this branch.";
                continue;
            }

            if ($product->status !== 'active') {
                $errors[] = "{$product->name}: Product is not available for sale.";
                continue;
            }

            if ($product->quantity < $item['quantity']) {
                $errors[] = "{$product->name}: Only {$product->quantity} available, but {$item['quantity']} requested.";
                continue;
            }
        }

        return $errors;
    }

    public function checkout(array $validated): Sale
    {
        $user = Auth::user();
        $branchId = $user->business_branch_id;

        $cartErrors = $this->validateCart($validated['items']);
        if (!empty($cartErrors)) {
            throw new Exception(implode('; ', $cartErrors), 422);
        }

        return DB::transaction(function () use ($validated, $user, $branchId) {
            $totalAmount = collect($validated['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $totalDiscount = collect($validated['items'])->sum(fn($i) => ($i['discount'] ?? 0) * $i['quantity']);

            if (isset($validated['sale_id'])) {
                $sale = Sale::where('id', $validated['sale_id'])
                    ->where('business_branch_id', $branchId)
                    ->where('status', 'held')
                    ->firstOrFail();
                $sale->update(['status' => 'completed', 'note' => $validated['note'] ?? $sale->note]);
            } else {
                $sale = Sale::create([
                    'business_branch_id' => $branchId,
                    'customer_id'        => $validated['customer_id'] ?? null,
                    'total_amount'       => $totalAmount,
                    'note'               => $validated['note'] ?? null,
                    'status'             => 'completed',
                ]);

                foreach ($validated['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $lineDiscount = ($item['discount'] ?? 0) * $item['quantity'];
                    $subtotal = ($item['quantity'] * $item['unit_price']) - $lineDiscount;

                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount'   => $item['discount'] ?? 0,
                        'subtotal'   => $subtotal,
                    ]);

                    $product->decrement('quantity', $item['quantity']);
                    $product->update(['last_sold_at' => now()]);

                    StockMovement::create([
                        'business_id'       => $user->business_id,
                        'business_branch_id' => $branchId,
                        'product_id'        => $item['product_id'],
                        'type'              => 'out',
                        'quantity'          => $item['quantity'],
                        'reference_type'    => Sale::class,
                        'reference_id'      => $sale->id,
                        'notes'             => 'POS sale',
                    ]);

                    if ($product->quantity <= $product->reorder_level) {
                        $this->notificationService->lowStockAlert(
                            $user,
                            $product->name ?? $product->id,
                            $product->quantity,
                            $product->reorder_level
                        );
                    }
                }
            }

            $totalPaid = 0;
 foreach ($validated['payments'] as $payment) {
                SalePayment::create([
                    'sale_id'       => $sale->id,
                    'method'        => $payment['method'],
                    'amount'        => $payment['amount'],
                    'paymentStatus' => 'paid',
                ]);
                $totalPaid += $payment['amount'];
            }

            $changeGiven = max(0, $totalPaid - ($totalAmount - $totalDiscount));

            $customer = isset($validated['customer_id'])
                ? Customer::with('user')->find($validated['customer_id'])?->user
                : null;
            $customerName = $customer ? trim($customer->firstname . ' ' . $customer->lastname) : 'Walk-in Customer';

            $this->cashFlowService->createCashFlowForSale($sale, $totalAmount - $totalDiscount, [
                'transaction_code'  => 'CF-POS-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                'currency'          => $validated['currency'] ?? 'UGX',
                'payment_status_id' => 1,
                'reference'         => null,
            ]);

            $this->notificationService->newSaleRecorded($user, number_format($totalAmount - $totalDiscount), $customerName);

            $this->createPosReceipt($sale, $validated, $totalPaid, $changeGiven);

            return $sale->load(['saleItems.product', 'receipt.items']);
        });
    }

    protected function createPosReceipt(Sale $sale, array $validated, float $amountPaid, float $changeGiven): Receipt
    {
        $user = Auth::user();
        $discountTotal = SaleItem::where('sale_id', $sale->id)->sum('discount');
        $subtotal = $sale->total_amount - $discountTotal;
        $paymentMethod = collect($validated['payments'])->pluck('method')->implode(', ');

        $receipt = Receipt::create([
            'receipt_number'     => $this->generateReceiptNumber(),
            'customer_id'        => $sale->customer_id,
            'user_id'            => $user->id,
            'business_id'        => $user->business_id,
            'business_branch_id' => $sale->business_branch_id,
            'sale_id'            => $sale->id,
            'subtotal'           => $subtotal,
            'discount'           => $discountTotal,
            'tax'                => 0,
            'total'              => $subtotal,
            'amount_paid'        => $amountPaid,
            'change_given'       => $changeGiven,
            'payment_method'     => $paymentMethod,
            'status'             => 'completed',
            'notes'              => $sale->note,
        ]);

        $saleItems = SaleItem::with('product')->where('sale_id', $sale->id)->get();

        foreach ($saleItems as $item) {
            ReceiptItem::create([
                'receipt_id'   => $receipt->id,
                'product_id'   => $item->product_id,
                'product_name' => $item->product?->name ?? 'Unknown',
                'sku'          => $item->product?->sku,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
                'discount'     => $item->discount ?? 0,
                'line_total'   => $item->subtotal,
            ]);
        }

        return $receipt->load('items');
    }

    protected function generateReceiptNumber(): string
    {
        $prefix = 'POS-';
        $date = now()->format('Ymd');
        $last = Receipt::whereDate('created_at', today())->count();
        return $prefix . $date . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function holdSale(array $items, ?int $customerId, ?string $notes): Sale
    {
        $user = Auth::user();
        $branchId = $user->business_branch_id;

        return DB::transaction(function () use ($items, $customerId, $notes, $user, $branchId) {
            $totalAmount = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);

            $sale = Sale::create([
                'business_branch_id' => $branchId,
                'user_id'            => $user->id,
                'customer_id'        => $customerId,
                'total_amount'       => $totalAmount,
                'note'               => $notes,
                'status'             => 'held',
            ]);

            foreach ($items as $item) {
                $lineDiscount = ($item['discount'] ?? 0) * $item['quantity'];
                $subtotal = ($item['quantity'] * $item['unit_price']) - $lineDiscount;

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount'   => $item['discount'] ?? 0,
                    'subtotal'   => $subtotal,
                ]);
            }

            return $sale->load(['saleItems.product', 'customer.user']);
        });
    }

    public function getHeldSales(): array
    {
        $user = Auth::user();

        return Sale::where('business_branch_id', $user->business_branch_id)
            ->where('user_id', $user->id)
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
            ->where('business_branch_id', $user->business_branch_id)
            ->where('user_id', $user->id)
            ->where('status', 'held')
            ->with('saleItems.product', 'customer.user')
            ->firstOrFail();
    }

    public function deleteHeldSale(int $id): void
    {
        $user = Auth::user();

        $sale = Sale::where('id', $id)
            ->where('business_branch_id', $user->business_branch_id)
            ->where('user_id', $user->id)
            ->where('status', 'held')
            ->firstOrFail();

        $sale->delete();
    }
}
