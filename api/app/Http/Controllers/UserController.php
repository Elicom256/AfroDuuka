<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Country;
use App\Models\Subscription;
use App\Models\User;
use App\Services\UserService;
use App\Support\Auth\RolePermissions;
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
    public function login(LoginRequest $request)
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

        // The client uses this to decide whether to send a brand-new signup to the
        // onboarding screen or into the app. Without it the UI has to infer the state
        // from a missing role, which is exactly the guess that produced a 404 on login.
        $onboarding = [
            'complete' => $user->business_id !== null,
        ];

        if ($user->business_id === null) {
            $onboarding['next'] = '/api/dashboard/business';
        }

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => $user,
            "country" => $country ?? "N/A",
            "onboarding" => $onboarding,
        ], 200);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user->fresh(),
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
        $roles = ['customer', 'supplier', 'Executive'];
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
            $q->where('name', '!=', 'Executive');
          })
        ->with(['business', 'role', "businessBranch"])
        ->get();
        return response()->json([
            'message' => 'Workers retrieved successfully',
            'data' => $users,
        ], 200);
    }


 //  branch worker
     public function worker(User $user)
    {
        abort_unless($user->business_id === Auth::user()?->business_id, 404);
        $user = $user->load("role");
        return response()->json([
            'message' => 'Worker retrieved successfully',
            'worker' => $user,
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

    // create account for executive with plan subscription
    public function signup(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Create the user account first. business_id stays null on purpose: the
            // person exists before they are a tenant. The account is immediately
            // restricted to onboarding by RequireBusiness until BusinessService::create()
            // links it to a business.
            $user = $this->userService->createAccount($validated);
            
            return response()->json([
                'message' => 'User created successfully',
                'data' => $user->load('business', 'role'),
                'onboarding' => [
                    'complete' => false,
                    'next' => '/api/dashboard/business',
                ],
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
    public function destroy(User $user)
    {
        try {
            abort_unless(Auth::check(), 403, 'Authentication required.');
            abort_unless(RolePermissions::canDelete(Auth::user()), 403, 'You do not have permission to delete users.');
            abort_unless($user->business_id === Auth::user()?->business_id, 404);
            abort_unless($user->id !== Auth::id(), 403, 'You cannot delete your own account.');

            $user->delete();
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