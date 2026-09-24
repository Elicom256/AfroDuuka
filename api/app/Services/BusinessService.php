<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Support\Facades\Request;

class BusinessService
{
    public function __construct()
    {
        //
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

        (new WhatsAppNotificationService())->queueBusinessNotification([
            'business_id' => $business->id,
            'type' => 'registration',
            'template_key' => 'registration.welcome',
            'recipient_phone' => $user->phone ?? $business->phone,
            'template_data' => [
                'business_name' => $business->name,
                'phone' => $user->phone ?? $business->phone,
            ],
        ]);

        // ======================= set starter plan ================= to create a plan based on what the user picked
        

        return $business;
    }

}