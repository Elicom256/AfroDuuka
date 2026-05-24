<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function create(array $data, callable $createProfile)
    {
        // ==== dependency injection + Callback pattern + transaction wrapping
        return DB::transaction(function () use ($data, $createProfile) {

            // username fallback
            $baseUsername = explode("@", $data["email"])[0];
            $username = "@" . $baseUsername . rand(11, 999);

            // 1. Create user (shared logic)
            $user = User::create([
                "firstname" => $data["firstname"],
                "lastname" => $data["lastname"],
                "email" => $data["email"],
                "username" => $data["username"] ?? $username,
                "password" => Hash::make($data["password"] ?? "password"),
                "phone" => $data["phone"] ?? null,
                "nin" => $data["nin"] ?? null,
                "address" => $data["address"] ?? null,
                "business_id" => $data["business_id"],
                "business_branch_id" => $data["business_branch_id"],
                "role_id" => $data["role_id"],
                "branch_powers" => $data["branch_powers"] ?? "none",
                "status" => $data["status"] ?? "active",
            ]);

            // 2. Attach profile (CUSTOM logic injected)
            $profile = $createProfile($user, $data);

            return $profile->load("user");
        });
    }


    // update
     public function updateProfile(User $user, array $data, callable $updateProfile)
    {
        return DB::transaction(function () use ($user, $data, $updateProfile) {
            // 1. Create user (shared logic)
            $user->update([
                "firstname" => $data["firstname"] ?? $user->firstname,
                "lastname" => $data["lastname"] ?? $user->lastname,
                "email" => $data["email"] ?? $user->email,
                "username" => $data["username"] ?? $user->username,
                "phone" => $data["phone"] ?? $user->phone,
                "nin" => $data["nin"] ?? $user->nin,
                "address" => $data["address"] ?? $user->address,
                "status" => $data["status"] ?? $user->status,
            ]);

            // 2. Attach profile (CUSTOM logic injected)
            $profile = $updateProfile($user, $data);
            return $profile->load("user");
        });
    }
}