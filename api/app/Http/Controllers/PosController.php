<?php

namespace App\Http\Controllers;

use App\Http\Requests\PosCheckoutRequest;
use App\Http\Requests\PosCustomerSearchRequest;
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

    public function holdSale(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'notes'       => 'nullable|string|max:500',
        ]);

        try {
            $sale = $this->posService->holdSale(
                $request->input('items'),
                $request->input('customer_id'),
                $request->input('notes')
            );
            return response()->json(['message' => 'Sale held successfully', 'data' => $sale], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to hold sale', 'error' => $e->getMessage()], 500);
        }
    }

    public function getHeldSales(): JsonResponse
    {
        try {
            $sales = $this->posService->getHeldSales();
            return response()->json(['message' => 'Held sales fetched', 'data' => $sales]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch held sales', 'error' => $e->getMessage()], 500);
        }
    }

    public function resumeHeldSale(int $id): JsonResponse
    {
        try {
            $sale = $this->posService->resumeHeldSale($id);
            return response()->json(['message' => 'Held sale resumed', 'data' => $sale]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Held sale not found', 'error' => $e->getMessage()], 404);
        }
    }

    public function deleteHeldSale(int $id): JsonResponse
    {
        try {
            $this->posService->deleteHeldSale($id);
            return response()->json(['message' => 'Held sale deleted']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete held sale', 'error' => $e->getMessage()], 404);
        }
    }
}