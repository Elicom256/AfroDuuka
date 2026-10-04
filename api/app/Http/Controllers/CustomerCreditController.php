<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerCreditPaymentRequest;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use App\Support\Auth\RolePermissions;
use Illuminate\Support\Facades\Auth;

class CustomerCreditController extends Controller
{
    public function __construct(private CustomerCreditService $creditService)
    {
    }

    public function balance(int $customer)
    {
        $customer = $this->customerForCurrentBusiness($customer);
        $branchId = (int) request('business_branch_id', Auth::user()->business_branch_id);
        $this->creditService->assertAccess(Auth::user(), $branchId);

        return response()->json([
            'customer_id' => $customer->id,
            'business_branch_id' => $branchId,
            'balance' => $this->creditService->balance($customer->id, $branchId),
        ]);
    }

    public function payment(StoreCustomerCreditPaymentRequest $request, int $customer)
    {
        // Recording a customer payment writes to the tenant-wide finance ledger and
        // moves the balance the business is owed, so it takes canManageBranch().
        //
        // Note the asymmetry with BusinessDebitController@pay, which stays open to the
        // floor: settling a supplier debt is a floor task, but the customer-credit
        // ledger is what credit limits and dunning are computed from, so writing to it
        // is not something a till role decides alone.
        abort_unless(RolePermissions::canManageBranch($request->user()), 403, 'You cannot record customer credit payments.');

        $customer = $this->customerForCurrentBusiness($customer);
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

    private function customerForCurrentBusiness(int $customerId): Customer
    {
        return Customer::whereKey($customerId)
            ->where(function ($query) {
                $query->where('business_id', Auth::user()->business_id)
                    ->orWhereHas('user', fn ($user) => $user->where('business_id', Auth::user()->business_id));
            })
            ->firstOrFail();
    }
}
