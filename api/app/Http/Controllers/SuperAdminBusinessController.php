<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessStatusRequest;
use App\Models\Business;
use App\Support\Auth\RolePermissions;

class SuperAdminBusinessController extends Controller
{
    protected function ensureSiteAdmin(): void
    {
        // RolePermissions::isPlatformOperator() rather than a raw name comparison:
        // it goes through roleName(), which lowercases and strips separators and
        // falls back to an unscoped role lookup, so a CoreSupport account (which has
        // no business by design) is still recognised. The inline strtolower() this
        // replaced read $user->role?->name, which the tenant scope blanks out for
        // exactly those users.
        abort_unless(
            RolePermissions::isPlatformOperator(auth()->user()),
            403,
            'Only the site administrator can manage businesses.'
        );
    }

    public function index()
    {
        $this->ensureSiteAdmin();

        $businesses = Business::with(['country', 'users'])->orderBy('created_at', 'desc')->get();

        return response()->json(['businesses' => $businesses, 'message' => 'Businesses retrieved']);
    }

    public function show(Business $business)
    {
        $this->ensureSiteAdmin();

        $business->load(['country', 'users', 'productCategories']);

        return response()->json(['business' => $business, 'message' => 'Business retrieved']);
    }

    public function updateStatus(UpdateBusinessStatusRequest $request, Business $business)
    {
        $this->ensureSiteAdmin();

        $request->validate([
            'status' => 'required|in:active,deactivated,banned',
        ]);

        $business->update(['status' => $request->status]);

        return response()->json(['business' => $business->fresh(), 'message' => 'Business status updated']);
    }
}
