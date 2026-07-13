<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

/**
 * Manages inter-branch stock transfers with dispatch/receive workflow.
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
     * Cancel a transfer.
     */
    public function cancel(StockTransfer $stockTransfer)
    {
        try {
            $transfer = $this->stockTransferService->cancel($stockTransfer);
            return response()->json(['message' => 'Stock transfer cancelled', 'data' => $transfer]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        $stockTransfer->delete();
        return response()->json(['message' => 'Stock transfer deleted']);
    }
}
