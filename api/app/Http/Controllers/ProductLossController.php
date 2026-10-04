<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductLossRequest;
use App\Models\Product;
use App\Models\ProductLoss;
use App\Services\InventoryService;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductLossController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $query = ProductLoss::with(['product', 'reportedBy']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('loss_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('loss_date', '<=', $request->date_to);
        }

        $totalLossCents = (clone $query)->sum('total_loss');

        $losses = $query->orderByDesc('loss_date')->paginate(20);

        return response()->json([
            'message' => 'Fetched product losses',
            'data' => $losses,
            'total_loss' => $totalLossCents / 100,
        ]);
    }

    public function store(StoreProductLossRequest $request): JsonResponse
    {
        // The gate is here, at the controller, and not inside
        // InventoryService::writeOff(). That service is also reached from the stock
        // count and expiry sweep, which Operations legitimately drives, so putting a
        // role check in it would fence Operations out of its own floor.
        //
        // Writing a loss off decrements quantity and books a cash_flows expense row.
        // InventoryService validates the reason and the quantity; it never asked who
        // was asking, so any signed-in account could remove stock and the money with
        // it. canModifyStock() is the existing expression of that capability.
        abort_unless(RolePermissions::canModifyStock($request->user()), 403, 'You cannot record product losses.');

        $data = $request->validated();

        $product = Product::whereKey($data['product_id'])->firstOrFail();

        $loss = $this->inventoryService->writeOff(
            $product,
            (int) $data['quantity'],
            $data['type'],
            $data['reason'] ?? null,
            null,
            $data['loss_date'] ?? null,
        );

        return response()->json([
            'message' => 'Product loss recorded',
            'data' => $loss,
        ], 201);
    }

    public function show(ProductLoss $productLoss): JsonResponse
    {
        $productLoss->load(['product', 'stockMovement', 'reportedBy']);

        return response()->json([
            'message' => 'Fetched product loss',
            'data' => $productLoss,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $query = ProductLoss::query();

        if ($request->filled('date_from')) {
            $query->whereDate('loss_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('loss_date', '<=', $request->date_to);
        }

        $byType = $query->selectRaw('type, SUM(total_loss) as total, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->type => ['total' => $row->total / 100, 'count' => $row->count]]);

        return response()->json([
            'message' => 'Loss summary',
            'by_type' => $byType,
            'grand_total' => $byType->sum('total'),
        ]);
    }
}
