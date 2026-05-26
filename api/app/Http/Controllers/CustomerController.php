<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Auth;

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
        $customers = Customer::with("user")->where("status", "active")->get();
        return response()->json(["message" => "Fetched all customers", "customers" => $customers], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();
        $customer = $this->customerService->createCustomer($validated);
        return response()->json(["message" => "Created a customer", "customer" => $customer], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $customer = $customer->load("user");
        return response()->json(["message" => "Fetched a customer", "customer" => $customer], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
         $validated = $request->validated();
         $data = $this->customerService->updateCustomer($customer,$validated);
        // $customer->update($validated);
        return response()->json(["message" => "Updated a customer", "customer" => $data], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(["message" => "Deleted a customer"], 201);
    }
}
