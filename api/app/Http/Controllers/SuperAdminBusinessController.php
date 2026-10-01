<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessStatusRequest;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SuperAdminBusinessController extends Controller
{
    protected function ensureSiteAdmin(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && strtolower((string) ($user->role?->name ?? '')) === 'siteadmin',
            403,
            'Only the site administrator can manage businesses.'
        );
    }

    public function index()
    {
        $this->ensureSiteAdmin();

        $businesses = Business::with(['country', 'users'])->orderBy('created_at', 'desc')->get();
        return response()->json(["businesses" => $businesses, "message" => "Businesses retrieved"]);
    }

    public function show(Business $business)
    {
        $this->ensureSiteAdmin();

        $business->load(['country', 'users', 'productCategories']);
        return response()->json(["business" => $business, "message" => "Business retrieved"]);
    }

    public function updateStatus(UpdateBusinessStatusRequest $request, Business $business)
    {
        $this->ensureSiteAdmin();

        $request->validate([
            'status' => 'required|in:active,deactivated,banned',
        ]);

        $business->update(['status' => $request->status]);
        return response()->json(["business" => $business->fresh(), "message" => "Business status updated"]);
    }
}