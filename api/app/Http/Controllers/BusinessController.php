<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Services\BusinessService;
use App\Services\CoreBusinessSettings;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessController extends Controller
{
    protected $businessService;

    protected $coreBusinessSettings;

    public function __construct(BusinessService $businessService, CoreBusinessSettings $coreBusinessSettings)
    {
        $this->businessService = $businessService;
        $this->coreBusinessSettings = $coreBusinessSettings;
    }

    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessRequest $request)
    {
        $user = $request->user();

        try {
            $business = $this->businessService->create($request->validated(), $user);
            $businessId = $business->id;
            $settings = $this->coreBusinessSettings->coreSettings($businessId);

            return response()->json([
                'message' => 'Business created successfully',
                'business' => $business,
                'core_settings' => $settings,
                // The onboarding screen lands on /verify-email right after this call
                // and needs to name the address the link went to. It is the business
                // email, which BusinessService falls back to the owner's.
                'email_verified' => false,
                'verification_email' => $business->email,
            ], 201);
        } catch (QueryException $e) {
            // Log the full SQL error for debugging check constraint violations (SQLSTATE 23514)
            $sqlState = $e->errorInfo[0] ?? $e->getCode();
            $sqlMessage = $e->getMessage();

            Log::error('Business creation failed with database error', [
                'sql_state' => $sqlState,
                'sql_message' => $sqlMessage,
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'user_id' => $user->id,
                'validated_data' => $request->validated(),
            ]);

            // Re-throw to let ApiExceptionRenderer handle it, but with more context in logs
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $business = $request->user()->business;
        if (! $business) {
            return response()->json(['message' => 'No business found'], 404);
        }
        $business->load('productCategories', 'users', 'country');

        return response()->json(['data' => $business]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessRequest $request)
    {
        $business = $request->user()->business;
        if (! $business) {
            return response()->json(['message' => 'No business found'], 404);
        }
        $business->update($request->validated());

        return response()->json(['message' => 'Business updated successfully', 'data' => $business]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Business $business)
    {
        //
    }
}
