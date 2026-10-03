<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\ProcurementService;
use App\Support\Auth\RolePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProcurementController extends Controller
{
    public function __construct(
        protected ProcurementService $procurementService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        try {
            $data = $this->procurementService->getProcurementOverview(
                $request->query('branch_id')
            );

            return response()->json([
                'message' => 'Procurement overview fetched',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch procurement overview',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function reorderSuggestions(Request $request): JsonResponse
    {
        try {
            $data = $this->procurementService->reorderSuggestions(
                $request->query('branch_id')
            );

            return response()->json([
                'message' => 'Reorder suggestions fetched',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch reorder suggestions',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with('items.product', 'supplier', 'user', 'approvedBy', 'receivedBy')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->where('business_branch_id', $request->branch_id);
        }

        $orders = $query->paginate(15);

        return response()->json([
            'message' => 'Purchase orders fetched',
            'data' => $orders,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(RolePermissions::canCreatePurchaseOrder($request->user()), 403);

        try {
            $validated = $request->validate([
                'supplier_id' => 'nullable|exists:suppliers,id',
                'business_branch_id' => 'nullable|exists:business_branches,id',
                'order_date' => 'nullable|date',
                'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            $order = $this->procurementService->createPurchaseOrder($validated);

            return response()->json([
                'message' => 'Purchase order created',
                'data' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(PurchaseOrder $purchase_order): JsonResponse
    {
        return response()->json([
            'message' => 'Purchase order fetched',
            'data' => $purchase_order->load('items.product', 'supplier', 'user', 'approvedBy', 'receivedBy'),
        ]);
    }

    public function approve(Request $request, PurchaseOrder $purchase_order): JsonResponse
    {
        abort_unless(RolePermissions::canApprovePurchaseOrder($request->user()), 403);

        try {
            $order = $this->procurementService->approvePurchaseOrder($purchase_order);

            return response()->json([
                'message' => 'Purchase order approved',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function order(PurchaseOrder $purchase_order): JsonResponse
    {
        abort_unless(RolePermissions::canReceivePurchaseOrder(Auth::user()), 403);

        try {
            $order = $this->procurementService->markAsOrdered($purchase_order);

            return response()->json([
                'message' => 'Purchase order marked as ordered',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark purchase order as ordered',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function receive(Request $request, PurchaseOrder $purchase_order): JsonResponse
    {
        abort_unless(RolePermissions::canReceivePurchaseOrder($request->user()), 403);

        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.received_quantity' => 'required|integer|min:0',
            ]);

            $receivedItems = [];
            foreach ($validated['items'] as $item) {
                $receivedItems[$item['id']] = $item['received_quantity'];
            }

            $order = $this->procurementService->receivePurchaseOrder($purchase_order, $receivedItems);

            return response()->json([
                'message' => 'Purchase order received',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to receive purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(PurchaseOrder $purchase_order): JsonResponse
    {
        abort_unless(RolePermissions::canCreatePurchaseOrder(Auth::user()), 403);

        try {
            $order = $this->procurementService->cancelPurchaseOrder($purchase_order);

            return response()->json([
                'message' => 'Purchase order cancelled',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel purchase order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
