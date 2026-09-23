<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxPaymentRequest;
use App\Http\Requests\TaxPaymentAnalyticsRequest;
use App\Http\Requests\UpdateTaxPaymentRequest;
use App\Http\Resources\TaxPaymentResource;
use App\Models\TaxPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $branchId = Auth::user()->business_branch_id;

        $query = TaxPayment::with('taxCategory')
            ->where('business_branch_id', $branchId);

        if ($request->filled('tax_category_id')) {
            $query->where('tax_category_id', $request->integer('tax_category_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('payment_date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('payment_date', '<=', $request->date('to'));
        }

        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->get();

        return response()->json([
            'message' => 'Tax payments fetched!',
            'payments' => TaxPaymentResource::collection($payments),
        ], 200);
    }

    public function store(StoreTaxPaymentRequest $request): JsonResponse
    {
        $payment = TaxPayment::create($request->validated());

        return response()->json([
            'message' => 'Tax payment recorded successfully!',
            'payment' => new TaxPaymentResource($payment->load('taxCategory')),
        ], 201);
    }

    public function show(TaxPayment $taxPayment): JsonResponse
    {
        $this->assertOwnBranch($taxPayment);

        return response()->json([
            'message' => 'Tax payment fetched!',
            'payment' => new TaxPaymentResource($taxPayment->load('taxCategory')),
        ], 200);
    }

    public function update(UpdateTaxPaymentRequest $request, TaxPayment $taxPayment): JsonResponse
    {
        $this->assertOwnBranch($taxPayment);

        $taxPayment->update($request->validated());

        return response()->json([
            'message' => 'Tax payment updated successfully!',
            'payment' => new TaxPaymentResource($taxPayment->load('taxCategory')),
        ], 200);
    }

    public function destroy(TaxPayment $taxPayment): JsonResponse
    {
        $this->assertOwnBranch($taxPayment);

        $taxPayment->delete();

        return response()->json([
            'message' => "Tax payment with id {$taxPayment->id} deleted successfully!",
        ], 200);
    }

    public function analytics(TaxPaymentAnalyticsRequest $request): JsonResponse
    {
        $branchId = Auth::user()->business_branch_id;

        $query = TaxPayment::where('business_branch_id', $branchId);

        if ($request->filled('tax_category_id')) {
            $query->where('tax_category_id', $request->integer('tax_category_id'));
        }

        [$from, $to] = $this->resolvePeriod($request);

        if ($from) {
            $query->whereDate('payment_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('payment_date', '<=', $to);
        }

        $totalPaid = (float) (clone $query)->sum('amount');
        $totalCount = (clone $query)->count();

        $grouped = (clone $query)
            ->with('taxCategory')
            ->select('tax_category_id')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('tax_category_id')
            ->get();

        $byCategory = $grouped->map(fn ($row) => [
            'tax_category_id' => $row->tax_category_id,
            'name' => $row->taxCategory?->name ?? 'Uncategorized',
            'total' => (float) $row->total,
        ]);

        return response()->json([
            'message' => 'Tax payment analytics fetched!',
            'analytics' => [
                'period' => [
                    'from' => $from?->toDateString(),
                    'to' => $to?->toDateString(),
                ],
                'total_paid' => $totalPaid,
                'total_count' => $totalCount,
                'by_category' => $byCategory,
            ],
        ], 200);
    }

    /**
     * Resolve the [from, to] payment_date range based on a custom range or a period preset.
     *
     * @return array{0: \Carbon\CarbonImmutable|null, 1: \Carbon\CarbonImmutable|null}
     */
    private function resolvePeriod(TaxPaymentAnalyticsRequest $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            return [$request->date('from')->startOfDay(), $request->date('to')->endOfDay()];
        }

        $period = $request->input('period');

        $start = match ($period) {
            'today' => now()->startOfDay(),
            'this_week' => now()->startOfWeek(),
            'this_month' => now()->startOfMonth(),
            'this_quarter' => now()->startOfQuarter(),
            'this_year' => now()->startOfYear(),
            default => null,
        };

        if ($start) {
            return [$start, now()->endOfDay()];
        }

        return [null, null];
    }

    private function assertOwnBranch(TaxPayment $taxPayment): void
    {
        if ($taxPayment->business_branch_id !== Auth::user()->business_branch_id) {
            abort(404, 'Tax payment not found.');
        }
    }
}