<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;

class SuperAdminBusinessController extends Controller
{
    public function index()
    {
        $businesses = Business::with(['country', 'users'])->orderBy('created_at', 'desc')->get();
        return response()->json(["businesses" => $businesses, "message" => "Businesses retrieved"]);
    }

    public function show(Business $business)
    {
        $business->load(['country', 'users', 'categories']);
        return response()->json(["business" => $business, "message" => "Business retrieved"]);
    }

    public function updateStatus(Request $request, Business $business)
    {
        $request->validate([
            'status' => 'required|in:active,deactivated,banned',
        ]);

        $business->update(['status' => $request->status]);
        return response()->json(["business" => $business->fresh(), "message" => "Business status updated"]);
    }
}
