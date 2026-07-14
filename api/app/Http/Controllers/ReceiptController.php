<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Receipt::with(['user', 'customer', 'items']);

        if ($user->role !== 'admin') {
            $query->where('business_branch_id', $user->business_branch_id);
        } else {
            $query->where('business_id', $user->business_id);
        }

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
        $user = Auth::user();
        if ($user->role !== 'admin' && $receipt->business_branch_id !== $user->business_branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $receipt->load(['user', 'customer', 'items.product', 'sale', 'businessBranch']);

        return response()->json([
            'message' => 'Receipt fetched successfully',
            'receipt' => $receipt,
        ]);
    }

    public function pdf(Receipt $receipt)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && $receipt->business_branch_id !== $user->business_branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $receipt->load(['user', 'customer', 'items', 'businessBranch']);

        $pdf = Pdf::loadView('pdfs.receipt', compact('receipt'));

        return $pdf->download("receipt-{$receipt->receipt_number}.pdf");
    }
}
