<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Support\ReceiptQrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function index()
    {
        $query = Receipt::with(['user', 'customer', 'items']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%");
            });
        }

        if ($customerId = request('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($userId = request('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($paymentMethod = request('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = request('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = request('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $perPage = request('per_page', 15);
        $receipts = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'message' => 'Receipts fetched successfully',
            'receipts' => $receipts,
        ]);
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['user', 'customer', 'items.product', 'sale', 'businessBranch']);

        return response()->json([
            'message' => 'Receipt fetched successfully',
            'receipt' => $receipt,
        ]);
    }

    public function pdf(Receipt $receipt)
    {
        $receipt->load(['user', 'customer', 'items', 'businessBranch.business']);

        $business = $receipt->businessBranch?->business;
        $businessName = $business?->name ?? '';
        $branchName = $receipt->businessBranch?->name ?? '';

        $logoBase64 = null;
        if ($business?->logo) {
            $logoPath = Storage::disk('public')->path("logo/{$business->logo}");
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/'.pathinfo($logoPath, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($logoPath));
            }
        }

        $qrBase64 = null;
        $platformUrl = config('app.url', 'https://duukaflow.com');
        try {
            $qrBase64 = ReceiptQrCode::svgDataUri($platformUrl);
        } catch (\Throwable $e) {
            // QR generation failure should not block PDF generation
        }

        $pdf = Pdf::loadView('pdfs.receipt', compact('receipt', 'businessName', 'branchName', 'logoBase64', 'qrBase64'));

        $acceptHeader = request()->header('Accept', '');
        if (str_contains($acceptHeader, '/json') || str_contains($acceptHeader, '+json')) {
            return response()->json([
                'message' => 'PDF generated successfully',
                'pdf' => base64_encode($pdf->output()),
                'filename' => "receipt-{$receipt->receipt_number}.pdf",
            ]);
        }

        return $pdf->download("receipt-{$receipt->receipt_number}.pdf");
    }
}
