<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Quotation;
use App\Services\QuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;

class QuotationController extends Controller
{
    public function __construct(protected QuotationService $quotations) {}

    public function index(): JsonResponse
    {
        $query = Quotation::with(['customer', 'items', 'acceptedOrder']);

        if ($status = request('status')) {
            if ($status === 'expired') {
                $query->whereIn('status', ['draft', 'sent'])
                    ->whereNotNull('valid_until')
                    ->whereDate('valid_until', '<', now()->toDateString());
            } else {
                $query->where('status', $status);
            }
        }

        if ($customerId = request('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($user_id = request('user_id')) {
            $query->where('user_id', $user_id);
        }

        if ($search = request('search')) {
            $query->where('quotation_number', 'like', "%{$search}%");
        }

        if ($dateFrom = request('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = request('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $perPage = request('per_page', 15);
        $quotations = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'message' => 'Quotations fetched successfully',
            'quotations' => $quotations,
        ]);
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $this->authorize('create', Quotation::class);

        try {
            $quotation = $this->quotations->create($request->validated());
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json([
            'message' => 'Quotation created successfully',
            'quotation' => $quotation,
        ], 201);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $this->authorize('view', $quotation);

        return response()->json([
            'message' => 'Quotation fetched successfully',
            'quotation' => $quotation->load(['items.product', 'customer', 'user', 'acceptedOrder']),
        ]);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        try {
            $updated = $this->quotations->update($quotation, $request->validated());
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 409);
        }

        return response()->json([
            'message' => 'Quotation updated successfully',
            'quotation' => $updated,
        ]);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->authorize('delete', $quotation);

        if (! in_array($quotation->status, ['draft', 'sent'], true)) {
            return response()->json(['message' => 'Only draft or sent quotations can be deleted.'], 409);
        }

        $quotation->items()->delete();
        $quotation->delete();

        return response()->json(['message' => 'Quotation deleted successfully']);
    }

    public function send(Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        try {
            $sent = $this->quotations->send($quotation);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 409);
        }

        return response()->json([
            'message' => 'Quotation marked as sent',
            'quotation' => $sent,
        ]);
    }

    public function accept(Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        try {
            $order = $this->quotations->accept($quotation);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 409);
        }

        return response()->json([
            'message' => 'Quotation accepted. Sales order created with inventory reserved.',
            'sale_order' => $order,
            'quotation' => $quotation->load(['items', 'acceptedOrder']),
        ]);
    }

    public function cancel(Quotation $quotation): JsonResponse
    {
        $this->authorize('update', $quotation);

        try {
            $cancelled = $this->quotations->cancel($quotation);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 409);
        }

        return response()->json([
            'message' => 'Quotation cancelled successfully',
            'quotation' => $cancelled,
        ]);
    }

    public function pdf(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load(['items.product', 'customer', 'user', 'businessBranch']);

        $pdf = Pdf::loadView('pdfs.quotation', compact('quotation'));

        $acceptHeader = request()->header('Accept', '');
        if (str_contains($acceptHeader, '/json') || str_contains($acceptHeader, '+json')) {
            return response()->json([
                'message' => 'PDF generated successfully',
                'pdf' => base64_encode($pdf->output()),
                'filename' => "quotation-{$quotation->quotation_number}.pdf",
            ]);
        }

        return $pdf->download("quotation-{$quotation->quotation_number}.pdf");
    }
}
