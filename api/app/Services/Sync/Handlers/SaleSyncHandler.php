<?php

namespace App\Services\Sync\Handlers;

use App\Http\Requests\PosCheckoutRequest;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\PosService;
use App\Services\Sync\SyncConflictException;
use App\Services\Sync\SyncHandler;
use App\Services\Sync\ValidatesPayload;
use App\Support\Auth\RolePermissions;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Replays an offline POS sale through the online checkout path.
 *
 * The payload is the exact shape `POST /pos/checkout` accepts (no
 * business_branch_id — PosCheckoutRequest merges the caller's branch), validated
 * by that same FormRequest, then handed to PosService::checkout so stock, tax,
 * receipts, and payments behave identically to a sale taken at the counter.
 *
 * Two server-side guards run before checkout, both because the device's cached
 * catalogue can be arbitrarily stale:
 *
 *  - **Stock** — if any product no longer holds the requested quantity the
 *    operation is a conflict (structured shortfall), not a silent partial sale.
 *  - **Price** — the cart's unit prices must match the server's current
 *    selling_price. The POS UI has no price editor, so a mismatch means the
 *    catalogue changed while the device was offline; accepting the stale price
 *    would sell below the agreed price, and rejecting with a generic validation
 *    error would hide why. Reported as a `price_mismatch` conflict.
 *
 * Resuming a held sale (sale_id present) skips both: that sale's prices and
 * quantities were agreed with the server at hold time, and checkout re-validates
 * ownership and status itself.
 */
class SaleSyncHandler implements SyncHandler
{
    public function __construct(private readonly PosService $posService) {}

    public function type(): string
    {
        return 'sale';
    }

    public function handle(array $payload, User $user, string $operationId): array
    {
        if (! $user->business_id) {
            throw new AuthorizationException('A business is required to sync sales.');
        }

        if (! RolePermissions::canOperateTill($user)) {
            throw new AuthorizationException('The till is available to staff accounts.');
        }

        $validated = ValidatesPayload::run(PosCheckoutRequest::class, $payload);
        $branchId = $validated['business_branch_id'];

        if (! BusinessBranch::query()->whereKey($branchId)->where('business_id', $user->business_id)->exists()) {
            throw new AuthorizationException('The selected branch is not part of your business.');
        }

        if (! empty($validated['customer_id']) && ! Customer::withoutGlobalScope('branch')
            ->where('business_id', $user->business_id)
            ->whereKey($validated['customer_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'customer_id' => 'The selected customer is not available in your business.',
            ]);
        }

        if (! isset($validated['sale_id'])) {
            $products = $this->loadCartProducts($validated, $user);

            $this->assertStockAvailable($validated, $products);
            $this->assertPricesMatchServer($validated, $products);
        }

        try {
            $sale = $this->posService->checkout($validated, $operationId);
        } catch (Exception $e) {
            // PosService re-checks stock under the row lock; losing that race is
            // the same shortfall the pre-check reports, just later.
            if ((int) $e->getCode() === 422
                && preg_match('/Only \d+ available, but \d+ requested\.$/', $e->getMessage()) === 1) {
                throw SyncConflictException::make('insufficient_stock', $e->getMessage());
            }

            throw $e;
        }

        return [
            'server_id' => (int) $sale->getKey(),
            'resource_type' => 'sale',
            'result' => [
                'sale_id' => (int) $sale->getKey(),
                'total_amount' => round((float) $sale->total_amount, 2),
                'status' => $sale->status,
            ],
        ];
    }

    /**
     * The cart's products, scoped to the sale's branch by the tenant/branch global
     * scopes. Products that do not resolve are reported together so one stale row
     * does not cost a round trip per item.
     *
     * @return Collection<int, Product>
     */
    private function loadCartProducts(array $validated, User $user): Collection
    {
        $productIds = collect($validated['items'])->pluck('product_id')->unique()->values()->all();

        $products = Product::query()
            ->where('business_branch_id', $validated['business_branch_id'])
            ->whereHas('businessBranch', fn ($query) => $query->where('business_id', $user->business_id))
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $missing = array_diff($productIds, $products->keys()->all());

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'items' => 'One or more products are not available in this branch: '.implode(', ', $missing).'.',
            ]);
        }

        return $products;
    }

    /**
     * Mirrors the in-transaction aggregation (the same product may appear on
     * several rows) so the pre-flight verdict equals what checkout would decide.
     *
     * @param  Collection<int, Product>  $products
     */
    private function assertStockAvailable(array $validated, Collection $products): void
    {
        $requestedByProduct = [];
        foreach ($validated['items'] as $item) {
            $requestedByProduct[$item['product_id']] = ($requestedByProduct[$item['product_id']] ?? 0) + (int) $item['quantity'];
        }

        foreach ($requestedByProduct as $productId => $requestedQuantity) {
            $product = $products->get($productId);
            $available = (int) $product->quantity;

            if ($available < $requestedQuantity) {
                throw SyncConflictException::make(
                    'insufficient_stock',
                    "{$product->name}: Only {$available} available, but {$requestedQuantity} requested.",
                    [
                        'product_id' => (int) $productId,
                        'requested' => $requestedQuantity,
                        'available' => $available,
                        'shortfall' => $requestedQuantity - $available,
                    ],
                );
            }
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function assertPricesMatchServer(array $validated, Collection $products): void
    {
        foreach ($validated['items'] as $item) {
            $product = $products->get($item['product_id']);
            $serverPrice = round((float) $product->selling_price, 2);
            $devicePrice = round((float) $item['unit_price'], 2);

            if (abs($serverPrice - $devicePrice) > 0.009) {
                throw SyncConflictException::make(
                    'price_mismatch',
                    "Price for {$product->name} is now {$serverPrice} but the offline sale recorded {$devicePrice}.",
                    [
                        'product_id' => (int) $product->getKey(),
                        'expected' => $serverPrice,
                        'received' => $devicePrice,
                    ],
                );
            }
        }
    }
}
