<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Country;
use App\Models\Subscription;
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
        $user = $request->user()->load("business.country", "businessBranch", "role");
        $country = $user->business ? Country::find($user->business->country_id) : null;
        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => $user,
            "country" => $country ?? "N/A"
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
        $roles = ['customer', 'supplier', 'admin'];
        $users = User::tenantVisible()
            ->whereHas('role', function ($q) use ($roles) {
            $q->whereNotIn('name', $roles);
          })
        ->with(['business', 'role', "businessBranch"])
        ->get();
        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => $users,
        ], 200);
    }

    // all branch workers
     public function workers()
    {
        $users = User::tenantVisible()
            ->whereHas('role', function ($q) {
            $q->where('name', '!=', 'admin');
          })
        ->with(['business', 'role', "businessBranch"])
        ->get();
        return response()->json([
            'message' => 'Workers retrieved successfully',
            'data' => $users,
        ], 200);
    }


 //  branch worker
     public function worker(User $worker)
    {
        abort_unless($worker->business_id === Auth::user()?->business_id, 404);
        $worker = $worker->load("role");
        return response()->json([
            'message' => 'Worker retrieved successfully',
            'worker' => $worker,
        ], 200);
    }

    //  branch worker
    //  public function updateWorker(User $worker)
    // {
    //     $worker = $worker->load("role");
    //     return response()->json([
    //         'message' => 'Worker retrieved successfully',
    //         'worker' => $worker,
    //     ], 200);
    // }

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

    // create account for admin with plan subscription
    public function signup(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Create the user account first
            $user = $this->userService->createAccount($validated);
            
            // If plan_id is provided, create subscription
            // if (isset($validated['plan_id'])) {
            //     $this->createUserSubscription($user, $validated['plan_id']);
            // }
            
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
        abort_unless($user->business_id === Auth::user()?->business_id, 404);
        $user = $this->userService->getUserById($user->id);
        return response()->json([
            'message' => 'Single User retrieved successfully!',
            'data' => $user,
        ], 200);
    }

    /**
     * Update the specified user in storage
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            // $user = Auth::user();
            abort_unless($user->business_id === Auth::user()?->business_id, 404);
            $validated = $request->validated();
            $user = $this->userService->updateUser($user,$validated);
            // $updated_user = $worker->update($validated);
            return response()->json([
                'message' => 'User updated successfully',
                'data' => $user,
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
    public function destroy(User $worker)
    {
        try {
            abort_unless($worker->business_id === Auth::user()?->business_id, 404);
            $worker->delete();
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