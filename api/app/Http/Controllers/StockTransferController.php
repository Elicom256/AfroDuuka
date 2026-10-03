<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Manages inter-branch stock transfers with dispatch/receive workflow.
 *
 * Every write here moves quantity between branches: dispatch decrements the source
 * and increments the destination, receive does it again on confirmation. The route
 * group carries no 'role' middleware — unlike expenses and the tax modules — so
 * before this pass any signed-in account could move stock between branches of the
 * business, and Operations, which is held out of canModifyStock everywhere else,
 * was not held out of it here.
 */
class StockTransferController extends Controller
{
    protected StockTransferService $stockTransferService;

    public function __construct(StockTransferService $stockTransferService)
    {
        $this->stockTransferService = $stockTransferService;
    }

    public function index()
    {
        $transfers = StockTransfer::where('business_id', auth()->user()->business_id)
            ->with(['fromBranch', 'toBranch', 'items.product', 'transferredBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['message' => 'Fetched stock transfers', 'data' => $transfers]);
    }

    public function store(StoreStockTransferRequest $request)
    {
        abort_unless(RolePermissions::canModifyStock(Auth::user()), 403, 'You cannot create stock transfers.');

        $transfer = $this->stockTransferService->create($request->validated());
        return response()->json(['message' => 'Stock transfer created', 'data' => $transfer], 201);
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['fromBranch', 'toBranch', 'items.product', 'transferredBy', 'receivedBy']);
        return response()->json(['message' => 'Fetched stock transfer', 'data' => $stockTransfer]);
    }

    /**
     * Dispatch transfer (decrement source stock).
     */
    public function dispatch(StockTransfer $stockTransfer)
    {
        abort_unless(RolePermissions::canModifyStock(Auth::user()), 403, 'You cannot dispatch stock transfers.');

        try {
            $transfer = $this->stockTransferService->dispatch($stockTransfer);
            return response()->json(['message' => 'Stock transfer dispatched', 'data' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Receive transfer (increment destination stock).
     */
    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        abort_unless(RolePermissions::canModifyStock(Auth::user()), 403, 'You cannot receive stock transfers.');

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
        ]);

        $receivedItems = collect($request->items)->pluck('quantity_received', 'id')->toArray();

        try {
            $transfer = $this->stockTransferService->receive($stockTransfer, $receivedItems);
            return response()->json(['message' => 'Stock transfer received', 'data' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Edit a transfer's editable fields.
     *
     * The route has always been published here (routes/stock-transfers.php) but no
     * method answered it, so any client PUT/PATCH got a 500 from an undefined method.
     * Only draft transfers are editable: once stock has physically moved the transfer
     * is a stock_movements fact, and editing it afterwards would leave the ledger
     * describing a movement that the header no longer matches.
     *
     * Status transitions deliberately do not go through here. dispatch/receive/cancel
     * each move stock as a side effect, so they stay dedicated endpoints where the
     * service owns the ledger. UpdateStockTransferRequest permits a 'status' key for
     * that reason, so it is dropped rather than trusted.
     */
    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer)
    {
        abort_unless(RolePermissions::canModifyStock(Auth::user()), 403, 'You cannot update stock transfers.');

        if ($stockTransfer->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft stock transfers can be edited. Use the dispatch, receive or cancel action.',
            ], 422);
        }

        $stockTransfer->update($request->safe()->except('status'));

        return response()->json([
            'message' => 'Stock transfer updated',
            'data' => $stockTransfer->fresh(['fromBranch', 'toBranch', 'items.product', 'transferredBy']),
        ]);
    }

    /**
     * Cancel a transfer.
     */
    public function cancel(StockTransfer $stockTransfer)
    {
        abort_unless(RolePermissions::canModifyStock(Auth::user()), 403, 'You cannot cancel stock transfers.');

        try {
            $transfer = $this->stockTransferService->cancel($stockTransfer);
            return response()->json(['message' => 'Stock transfer cancelled', 'data' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        abort_unless(RolePermissions::canDelete(Auth::user()), 403, 'You cannot delete stock transfers.');

        $stockTransfer->delete();
        return response()->json(['message' => 'Stock transfer deleted']);
    }
}
