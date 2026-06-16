<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Services\BusinessService;
use App\Services\CoreBusinessSettings;
use Illuminate\Http\Request;

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
        
        $business = $this->businessService->create( $request->validated(), $user);
        $businessId = $business->id;
        $settings = $this->coreBusinessSettings->coreSettings($businessId);
        return response()->json([
            'message' => 'Business created successfully',
            'business' => $business,
            "core_settings" => $settings
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $business = $request->user()->business;
        if (!$business) {
            return response()->json(['message' => 'No business found'], 404);
        }
        $business->load('categories', 'users');
        return response()->json(['data' => $business]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessRequest $request)
    {
        $business = $request->user()->business;
        if (!$business) {
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
