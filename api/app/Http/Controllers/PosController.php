<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\PosCheckoutRequest;
use App\Http\Requests\PosCustomerSearchRequest;
use App\Http\Requests\PosProductSearchRequest;
use App\Services\PosService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            // `ConvertEmptyStringsToNull` turns an empty catalog warm-up query into
            // null; coerce it so the empty-query branch in the service is reached.
            $products = $this->posService->searchProducts((string) $request->input('q'));

            return response()->json(['message' => 'Products fetched', 'data' => $products]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Search failed', 'error' => 'An error occurred. Please try again.'], 500);
        }
    }

    public function byBarcode(string $barcode): JsonResponse
    {
        if (trim($barcode) === '') {
            return response()->json(['message' => 'Barcode is required'], 422);
        }

        $product = $this->posService->scanByBarcode($barcode);

        if (empty($product)) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(['message' => 'Product found', 'data' => $product]);
    }

    public function searchCustomers(PosCustomerSearchRequest $request): JsonResponse
    {
        try {
            $customers = $this->posService->searchCustomers($request->input('q'));

            return response()->json(['message' => 'Customers fetched', 'data' => $customers]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Search failed', 'error' => 'An error occurred. Please try again.'], 500);
        }
    }

    public function validateCart(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
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
                'sale' => $sale,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (InsufficientStockException $e) {
            // Handled ahead of the generic catch below because it is a condition the
            // till operator can act on: the shortfall is reported so the cart can be
            // corrected and retried, rather than the operator reading "Checkout failed"
            // and retrying the identical cart against unchanged stock.
            Log::info('POS checkout rejected for insufficient stock', $e->context());

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['stock' => $e->context()],
            ], 409);
        } catch (\Exception $e) {
            Log::error($e);
            $status = $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            return response()->json([
                'message' => 'Checkout failed',
                'error' => 'An error occurred. Please try again.',
            ], $status);
        }
    }

    public function holdSale(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string|max:500',
            'business_branch_id' => 'nullable|integer|exists:business_branches,id',
        ]);

        try {
            $sale = $this->posService->holdSale(
                $request->input('items'),
                $request->input('customer_id'),
                $request->input('notes'),
                $request->input('business_branch_id')
            );

            return response()->json(['message' => 'Sale held successfully', 'data' => $sale], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to hold sale', 'error' => 'An error occurred. Please try again.'], 500);
        }
    }

    public function getHeldSales(): JsonResponse
    {
        try {
            $sales = $this->posService->getHeldSales();

            return response()->json(['message' => 'Held sales fetched', 'data' => $sales]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch held sales', 'error' => 'An error occurred. Please try again.'], 500);
        }
    }

    public function resumeHeldSale(int $id): JsonResponse
    {
        try {
            $sale = $this->posService->resumeHeldSale($id);

            return response()->json(['message' => 'Held sale resumed', 'data' => $sale]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Held sale not found', 'error' => 'An error occurred. Please try again.'], 404);
        }
    }

    public function deleteHeldSale(int $id): JsonResponse
    {
        try {
            $this->posService->deleteHeldSale($id);

            return response()->json(['message' => 'Held sale deleted']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete held sale', 'error' => 'An error occurred. Please try again.'], 404);
        }
    }
}
