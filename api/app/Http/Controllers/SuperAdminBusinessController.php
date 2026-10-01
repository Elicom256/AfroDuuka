<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessStatusRequest;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SuperAdminBusinessController extends Controller
{
    public function __construct()
    {
        Gate::before(function ($user, $ability, ...$args) {
            if (in_array($user->role->name ?? [], ['siteadmin'])) {
                return true;
            }
        });
    }

    public function index()
    {
        Gate::allows('viewAny', Business::class);
        $businesses = Business::with(['country', 'users'])->orderBy('created_at', 'desc')->get();
        return response()->json(["businesses" => $businesses, "message" => "Businesses retrieved"]);
    }

    public function show(Business $business)
    {
        Gate::allows('view', $business);
        $business->load(['country', 'users', 'productCategories']);
        return response()->json(["business" => $business, "message" => "Business retrieved"]);
    }

    public function updateStatus(UpdateBusinessStatusRequest $request, Business $business)
    {
        Gate::allows('updateStatus', $business);

        $request->validate([
            'status' => 'required|in:active,deactivated,banned',
        ]);

        $business->update(['status' => $request->status]);
        return response()->json(["business" => $business->fresh(), "message" => "Business status updated"]);
    }
}