<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Support\Tenant\EffectiveBranchScope;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    protected CashFlowService $cashFlowService;

    protected AnalyticsTrendHelper $analyticsTrendHelper;

    public function __construct(CashFlowService $cashFlowService, AnalyticsTrendHelper $analyticsTrendHelper)
    {
        $this->cashFlowService = $cashFlowService;
        $this->analyticsTrendHelper = $analyticsTrendHelper;
    }

    public function savePurchase($validated, ?string $business_branch_id = null)
    {
        $notificationService = app(NotificationService::class);
        $user = Auth::user();

        $branchId = $validated['business_branch_id'] ?? $business_branch_id ?? $user?->business_branch_id;
        $resolved = $user ? EffectiveBranchScope::branchesFor($user) : null;
        if ($branchId && $resolved !== null) {
            [, $branchIds] = $resolved;
            if (! in_array($branchId, $branchIds, true)) {
                throw new Exception('Branch is not within your allowed scope', 403);
            }
        }

        $total_amount = collect($validated['items'])->sum(fn ($i) => $i['cost_price'] * $i['quantity']);

        return DB::transaction(function () use ($validated, $branchId, $total_amount, $notificationService, $user) {
            $status = $validated['status'] ?? 'completed';
            $isCompleted = $status === 'completed';

            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'business_branch_id' => $branchId,
                'total_amount' => $total_amount,
                'status' => $status,
                'note' => $validated['note'] ?? null,
                'received_at' => $isCompleted ? now() : null,
                'received_by' => $isCompleted ? $user?->id : null,
            ]);

            foreach ($validated['items'] as $item) {
                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'selling_price' => $item['selling_price'] ?? null,
                    'subtotal' => $item['cost_price'] * $item['quantity'],
                ]);

                if ($isCompleted) {
                    $this->applyReceivedToProduct($purchaseItem, (int) $purchaseItem->quantity);
                }
            }

            $supplier = Supplier::find($purchase->supplier_id);
            $this->cashFlowService->createCashFlowForPurchase($purchase, $total_amount, $validated);
            $notificationService->newPurchaseRecorded($user, $supplier?->company_name ?? 'Supplier', number_format($total_amount), $purchase->id);

            return $purchase->load('purchaseItems');
        });
    }

    public function receivePurchase(Purchase $purchase, array $receivedItems, ?int $receivedBy = null): Purchase
    {
        if ($purchase->status === 'completed' && ! is_null($purchase->received_at)) {
            throw ValidationException::withMessages([
                'purchase' => 'This purchase has already been received and stock has been updated.',
            ]);
        }

        return DB::transaction(function () use ($purchase, $receivedItems, $receivedBy) {
            $userId = $receivedBy ?? Auth::id();

            foreach ($purchase->purchaseItems as $item) {
                $receivedQty = (int) ($receivedItems[$item->id] ?? $item->quantity);

                if ($receivedQty < 0 || $receivedQty > (int) $item->quantity) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}.quantity" => "Received quantity for item {$item->product_id} exceeds the ordered quantity.",
                    ]);
                }

                $this->applyReceivedToProduct($item, $receivedQty);
            }

            $purchase->update([
                'status' => 'completed',
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            return $purchase->fresh()->load('purchaseItems');
        });
    }

    private function applyReceivedToProduct(PurchaseItem $item, int $receivedQty): void
    {
        if ($receivedQty <= 0) {
            return;
        }

        $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
        if (! $product) {
            return;
        }

        // Purchases can update the product’s pricing basis, but they must not inflate
        // the branch’s remaining stock. When a manager records a completed purchase for
        // more units than the product actually has available, the stock must remain at
        // the real available level instead of being incremented to an incorrect total.
        $product->forceFill([
            'cost_price' => (float) $item->cost_price,
            'selling_price' => $item->selling_price !== null ? (float) $item->selling_price : $product->selling_price,
        ])->save();
    }

    public function analytics(string $period = 'last_7_days')
    {
        $query = Purchase::where('status', 'completed');

        $days = $this->analyticsTrendHelper->getDaysFromPeriod($period);

        $startDate = $period === 'today' ? Carbon::today() : Carbon::now()->subDays($days - 1);
        $query->where('created_at', '>=', $startDate);
        $purchases = $query->get();
        $totalPurchases = $purchases->sum('total_amount');
        $totalTransactions = $purchases->count();
        $avgPurchases = $totalPurchases ? $totalPurchases / $totalTransactions : 0;
        $testAvg = $purchases->average('total_amount');

        $purchaseTrend = $purchases->groupBy(function ($purchase) {
            return Carbon::parse($purchase->created_at)->format('M d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('M d'),
                'amount' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->values();

        $purchasesTrend = $this->analyticsTrendHelper->fillMissingDates($purchaseTrend, $days);

        return [
            'total_purchases' => round($totalPurchases, 2),
            'avg_purchase' => round($avgPurchases, 2),
            'test_avg' => round($testAvg, 2),
            'total_transactions' => $totalTransactions,
            'purchase_trend' => $purchasesTrend,
            'period' => $period,
            'lable' => 'purchases',
        ];
    }
}
