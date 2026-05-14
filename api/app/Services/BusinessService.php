<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\User;
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

        // Create the admin role for this business
        $adminRole = Role::create([
            'name' => 'admin',
            'business_id' => $business->id,
        ]);

        // Update the user's profile with business_id and role_id
        $user->update([
            'business_id' => $business->id,
            'role_id' => $adminRole->id,
        ]);
        BusinessBranch::create([
            "business_id" => $business->id, 
        ]);

        return $business;
    }

}