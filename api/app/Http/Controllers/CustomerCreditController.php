<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerCreditPaymentRequest;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\Auth;

class CustomerCreditController extends Controller
{
    public function __construct(private CustomerCreditService $creditService)
    {
    }

    public function balance(Customer $customer)
    {
        $branchId = (int) request('business_branch_id', Auth::user()->business_branch_id);
        $this->creditService->assertAccess(Auth::user(), $branchId);

        return response()->json([
            'customer_id' => $customer->id,
            'business_branch_id' => $branchId,
            'balance' => $this->creditService->balance($customer->id, $branchId),
        ]);
    }

    public function payment(StoreCustomerCreditPaymentRequest $request, Customer $customer)
    {
        $data = $request->validated();
        $payment = $this->creditService->recordPayment(
            Auth::user(),
            $customer->id,
            (int) $data['business_branch_id'],
            (float) $data['amount'],
            $data['reference'] ?? null,
            $data['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Customer credit payment recorded.',
            'data' => $payment,
            'balance' => $this->creditService->balance($customer->id, (int) $data['business_branch_id']),
        ], 201);
    }
}
