<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Authenticate a user and generate API token
     */
    public function login(array $credentials)
    {
        // Find user by email or username
        $user = User::where('email', $credentials['email'])
            ->orWhere('username', $credentials['email'])
            ->first();

        // Verify user exists and password matches
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        // Generate API token for Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load('business', 'role'),
            'token' => $token,
        ];
    }



    //  * Create a new user account (adding worker)
    public function signupUser(array $data)
    {
        $admin = Auth::user();
            $user = User::create([
            'email' => $data['email'],
            "firstname" => $data["firstname"] ?? $data["name"] ?? null,
            "lastname" => $data["lastname"] ?? null,
            'username' => "@" . ($data['name'] ?? $data['firstname'] ?? $data['email']),
            'phone' => $data['phone'],
            'password' => Hash::make("password"),
            'business_id' => $admin->business_id,
            'role_id' => $data['role_id'],
            "nin" => $data["nin"] ?? null
        ]);

        Worker::create([
            "user_id" => $user->id,
        ]);
        return $user;
    }


    // create account for admin
    public function createAccount(array $data){
        return User::create([
            "firstname" => $data['firstname'] ?? $data['name'] ?? null,
            "lastname" => $data['lastname'] ?? null,
            'email' => $data['email'],
            'username' => "@" . ($data['name'] ?? $data['username'] ?? $data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password'] ?? "password"),
            // 'business_id' => $data['business_id'] ?? null,
            // 'role_id' => $adminRoleId,
        ]);
    }
    /**
     * Get all users with relations
     */


    /**
     * Get a single user by ID with relations
     */
    public function getUserById(int $id)
    {
        return User::tenantVisible()->with('business', 'role')->findOrFail($id);
    }

    /**
     * Update user information
     */
    public function updateUser(User $user, array $validated)
    {
       $user->update([
            'email' => $validated['email'] ?? $user->email,
            'username' => $validated['username'] ?? $user->username,
            'role_id' => $validated['role_id'] ?? $user->role_id,
        ]);
        $worker = Worker::where("user_id", $user?->id)->first();
        if ($worker) {
            $worker->update([
                "firstname" => $validated["firstname"] ?? $worker->firstname ,
                "lastname" => $validated["lastname"] ?? $worker->lastname,
                "nin" => $validated["nin"] ?? $worker->nin
            ]);
        }
            
        return $user->load('business', 'role');
    }

    /**
     * Delete a user
     */

}