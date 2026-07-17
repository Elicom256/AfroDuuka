<?php

namespace App\Http\Controllers;

use App\Http\Requests\PosCheckoutRequest;
use App\Http\Requests\PosCustomerSearchRequest;
use App\Http\Requests\PosHoldCartRequest;
use App\Http\Requests\PosProductSearchRequest;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function searchProducts(PosProductSearchRequest $request): JsonResponse
    {
        try {
            $products = $this->posService->searchProducts($request->input('q'));
            return response()->json(['message' => 'Products fetched', 'data' => $products]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Search failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function searchCustomers(PosCustomerSearchRequest $request): JsonResponse
    {
        try {
            $customers = $this->posService->searchCustomers($request->input('q'));
            return response()->json(['message' => 'Customers fetched', 'data' => $customers]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Search failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function validateCart(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $errors = $this->posService->validateCart($request->input('items'));

        if (empty($errors)) {
            return response()->json(['message' => 'Cart is valid', 'valid' => true]);
        }

        return response()->json(['message' => 'Cart has errors', 'valid' => false, 'errors' => $errors], 422);
    }

    public function checkout(PosCheckoutRequest $request): JsonResponse
    {
        try {
            $sale = $this->posService->checkout($request->validated());
            return response()->json([
                'message' => 'Sale completed successfully!',
                'sale'    => $sale,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Checkout failed',
                'error'   => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function holdCart(PosHoldCartRequest $request): JsonResponse
    {
        try {
            $heldCart = $this->posService->holdCart(
                $request->input('items'),
                $request->input('customer_id'),
                $request->input('notes')
            );
            return response()->json(['message' => 'Cart held successfully', 'data' => $heldCart], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to hold cart', 'error' => $e->getMessage()], 500);
        }
    }

    public function getHeldCarts(): JsonResponse
    {
        try {
            $carts = $this->posService->getHeldCarts();
            return response()->json(['message' => 'Held carts fetched', 'data' => $carts]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch held carts', 'error' => $e->getMessage()], 500);
        }
    }

    public function resumeCart(int $id): JsonResponse
    {
        try {
            $heldCart = $this->posService->resumeCart($id);
            return response()->json(['message' => 'Cart resumed', 'data' => $heldCart]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Cart not found', 'error' => $e->getMessage()], 404);
        }
    }

    public function deleteHeldCart(int $id): JsonResponse
    {
        try {
            $this->posService->deleteHeldCart($id);
            return response()->json(['message' => 'Held cart deleted']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete held cart', 'error' => $e->getMessage()], 404);
        }
    }
}
