<?php

namespace App\Services;

use App\Models\User;
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

    /**
     * Logout user and revoke all tokens
     */
    public function logout(User $user): bool
    {
        // Revoke all tokens for the user
        $user->tokens()->delete();
        return true;
    }

    /**
     * Get authenticated user with relations
     */
    public function getAuthenticatedUser(User $user)
    {
        return $user->load('business', 'role');
    }

    /**
     * Create a new user account
     */
    public function signupUser(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'business_id' => $data['business_id'] ?? null,
            'role_id' => $data['role_id'] ?? null,
        ]);
    }

    /**
     * Get all users with relations
     */
    public function getAllUsers()
    {
        return User::with('business', 'role')->get();
    }

    /**
     * Get a single user by ID with relations
     */
    public function getUserById(int $id)
    {
        return User::with('business', 'role')->findOrFail($id);
    }

    /**
     * Update user information
     */
    public function updateUser(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'username' => $data['username'] ?? $user->username,
            'business_id' => $data['business_id'] ?? $user->business_id,
            'role_id' => $data['role_id'] ?? $user->role_id,
        ]);

        return $user->load('business', 'role');
    }

    /**
     * Delete a user
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }
}