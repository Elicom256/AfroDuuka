<?php

namespace App\Services;

use App\Events\WhatsAppNotificationEvents\BusinessRegistered;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class BusinessService
{
    public function __construct()
    {
        //
    }

    /**
     * businesses.country_id is NOT NULL but the registration form does not collect a
     * country, so it has to be resolved here or business creation fails outright.
     * Uganda is the primary market (it is also the +256 default used across the
     * codebase); fall back to the first seeded country if Uganda is absent.
     */
    private function resolveDefaultCountryId(): ?int
    {
        return Country::query()
            ->where('iso_alpha2', 'UG')
            ->orWhere('name', 'Uganda')
            ->orderBy('id')
            ->value('id')
            ?? Country::query()->orderBy('id')->value('id');
    }

    public function create(array $data, User $user): Business
    {
        // Create the business with the user's phone
        $business = Business::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? $user->email,
            'phone' => $user->phone,
            'address' => $data['address'],
            'business_category_id' => $data['business_category_id'],
            'country_id' => $data['country_id'] ?? $this->resolveDefaultCountryId(),
        ]);

        // Dispatch business registration event
        event(new BusinessRegistered($business));

        // Create the admin role for this business
        $adminRole = Role::create([
            'name' => 'admin',
            'business_id' => $business->id,
        ]);

        $existingRoleNames = Role::where('business_id', $business->id)->pluck('name')->all();
        $new_roles = ["admin", "manager", "editor", "staff", "worker", "customer", "supplier"];
        foreach ($new_roles as $new_role) {
            if (in_array($new_role, $existingRoleNames, true)) {
                continue;
            }
            Role::create([
                "name" => $new_role,
                'business_id' => $business->id,
            ]);
            $existingRoleNames[] = $new_role;
        }
        // Update the user's profile with business_id and role_id
        $user->update([
            'business_id' => $business->id,
            'role_id' => $adminRole->id,
        ]);
        BusinessBranch::create([
            "business_id" => $business->id, 
        ]);

        // ======================= set starter plan ================= to create a plan based on what the user picked
        

        return $business;
    }

}