<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected UserService $userService;
// Dipendency Injection(DI)
    public function __construct(UserService $userService)
    {
        // Inject UserService for business logic
        $this->userService = $userService;
    }

    /**
     * Authenticate user and generate token (Login)
     */
    public function login(StoreUserRequest $request)
    {
        try {
            $result = $this->userService->login($request->validated());
            return response()->json([
                'message' => 'Login successful!',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Get authenticated user (Me)
     */
    public function me(Request $request)
    {
        // Return currently authenticated user with relations
        $user = $request->user()->load("business", "role");
        return response()->json([
            'message' => 'User retrieved successfully!',
            'data' => $user,
        ], 200);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request)
    {
        // Revoke all tokens for authenticated user
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logout successful!',
        ], 200);
    }

    /**
     * Display a listing of all users
     */
    public function index()
    {
        $user = Auth::user();
        $users = User::where("business_id", $user->business_id)
        ->whereHas('role', function ($q) {
            $q->where('name', '!=', 'admin');
          })
        ->with(['business', 'role', "businessBranch"])
        ->get();
        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => $users,
        ], 200);
    }

    /**
     * Store a newly created user in storage
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->signupUser($request->validated());
            return response()->json([
                'message' => 'User created successfully',
                'data' => $user->load('business', 'role'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User creation failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // create account for admin
    public function signup(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->createAccount($request->validated());
            return response()->json([
                'message' => 'User created successfully',
                'data' => $user->load('business', 'role'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User creation failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user = $this->userService->getUserById($user->id);
        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => $user,
        ], 200);
    }

    /**
     * Update the specified user in storage
     */
    public function update(UpdateUserRequest $request, User $worker)
    {
        try {
            $user = Auth::user();
            $validated = $request->validated();
            $updated_user = $worker->update($validated);
            return response()->json([
                'message' => 'User updated successfully',
                'data' => $updated_user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User update failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(User $user)
    {
        try {
            $this->userService->deleteUser($user);
            return response()->json([
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User deletion failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}