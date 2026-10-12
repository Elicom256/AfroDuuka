<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Support\ListLimit;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Customer::class);
        $customers = ListLimit::apply(
            Customer::with('user')->where('status', 'active')->orderByDesc('created_at'),
            request(),
            ListLimit::RECORDS
        );

        return response()->json([
            'message' => 'Fetched all customers',
            'customers' => $customers,
            ...ListLimit::truncationNotice(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);
        $allowed = CustomersSettings::value('status');
        abort_if($allowed !== 'enabled', 403, 'Customer creation is disabled.');
        $validated = $request->validated();
        $customer = $this->customerService->createCustomer($validated);

        return response()->json(['message' => 'Created a customer', 'customer' => $customer], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        $customer = $customer->load('user');

        return response()->json(['message' => 'Fetched a customer', 'customer' => $customer], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $validated = $request->validated();
        $data = $this->customerService->updateCustomer($customer, $validated);

        return response()->json(['message' => 'Updated a customer', 'customer' => $data], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        $customer->delete();

        return response()->json(['message' => 'Deleted a customer'], 201);
    }
}
