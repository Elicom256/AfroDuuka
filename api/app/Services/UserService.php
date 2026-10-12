<?php

namespace App\Services;

use App\Exceptions\TooManyLoginAttemptsException;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private readonly LoginThrottle $loginThrottle) {}

    /**
     * Normalise a handle to the stored form: exactly one leading "@", lowercased.
     *
     * Idempotent on purpose. The client is not trusted to send a bare handle — a caller
     * that posts "@amina" must not end up storing "@@amina", which is what an
     * unconditional '"@" . $value' produced. ltrim() rather than a single-character
     * strip so any number of stray prefixes collapses.
     */
    public static function normalizeUsername(?string $value): ?string
    {
        $bare = ltrim(trim((string) $value), '@');

        if ($bare === '') {
            return null;
        }

        return '@'.strtolower($bare);
    }

    /**
     * Resolve a handle collision the way StoreUserRequest documents: @jane, @jane2, @jane3…
     *
     * The request deliberately carries no `unique` rule on username — a second Jane must
     * not be refused over a field she never typed — so the uniquification has to happen
     * here. The column's unique index remains the backstop.
     */
    public static function uniqueUsername(string $base): string
    {
        $candidate = self::normalizeUsername($base) ?? '@user';

        if (User::where('username', $candidate)->doesntExist()) {
            return $candidate;
        }

        $root = rtrim($candidate, '0123456789');
        $suffix = strlen($candidate) - strlen($root);

        do {
            $candidate = $root.($suffix + 1);
            $suffix++;
        } while (User::where('username', $candidate)->exists());

        return $candidate;
    }

    /**
     * Authenticate a user and generate API token
     */
    public function login(array $credentials)
    {
        // Find user by email or username. The username column stores the handle with
        // its "@" prefix, so a caller who types "amina" must still match "@amina".
        $input = (string) $credentials['email'];
        $bare = ltrim($input, '@');

        $ip = (string) (request()->ip() ?? 'unknown');

        // Checked before the password so a locked account cannot be probed at all, and
        // before the user lookup so the response does not reveal whether the address
        // exists — a locked non-existent account and a locked real one answer alike.
        if ($this->loginThrottle->tooManyAttempts($input)) {
            throw new TooManyLoginAttemptsException(
                $this->loginThrottle->availableIn($input)
            );
        }

        $user = User::where('email', $input)
            ->orWhere('username', $input)
            ->orWhere('username', $bare)
            ->orWhere('username', '@'.$bare)
            ->first();

        // Verify user exists and password matches
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->loginThrottle->hit($input, $ip);

            // The attempt counter is only mentioned once the account is actually
            // locked. Telling a caller "2 attempts left" while they are still guessing
            // tells them the address is real, which is the one thing the single
            // "invalid credentials" message exists to withhold.
            $remaining = $this->loginThrottle->remaining($input);

            throw ValidationException::withMessages([
                'email' => [
                    $remaining === 0
                        ? 'Too many failed attempts. Try again later.'
                        : 'The provided credentials are invalid.',
                ],
            ]);
        }

        // A suspended or removed account must not be able to authenticate. Without this
        // check the status column is decorative: deactivating someone leaves their
        // existing token working and still lets them sign in again.
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        // Correct credentials clear both counters: the failures that led here were the
        // user's own typos, and keeping them would lock them out right after a typo
        // they just fixed.
        $this->loginThrottle->clear($input, $ip);

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
        $executive = Auth::user();
        $user = User::create([
            'email' => $data['email'],
            'firstname' => $data['firstname'] ?? $data['name'] ?? null,
            'lastname' => $data['lastname'] ?? null,
            'username' => self::uniqueUsername($data['name'] ?? $data['firstname'] ?? $data['email']),
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'business_id' => $executive->business_id,
            'role_id' => $data['role_id'],
            'nin' => $data['nin'] ?? null,
        ]);

        Worker::create([
            'user_id' => $user->id,
        ]);

        $role = Role::find($data['role_id']);
        ActivityLog::create([
            'log_name' => 'Permission',
            'description' => 'User role assigned',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_type' => User::class,
            'causer_id' => $executive->id,
            'properties' => ['attributes' => ['role_id' => $data['role_id'], 'role_name' => $role?->name]],
        ]);

        return $user;
    }

    // create account for executive
    public function createAccount(array $data)
    {
        // The signup form historically posted a single `name` field while the columns
        // are firstname/lastname, and the validation rules for those two were commented
        // out — so $request->validated() dropped them and every signup stored a NULL
        // name. Both are handled here so the account is named correctly either way.
        [$firstName, $lastName] = $this->splitName($data);

        return User::create([
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $data['email'],
            'username' => self::uniqueUsername(
                $data['username'] ?? $data['firstname'] ?? $data['name'] ?? $data['email']
            ),
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            // business_id and role_id are deliberately absent. A self-serve signup
            // creates the person first and the business second; RequireBusiness blocks
            // every tenant route until BusinessService::create() links them.
        ]);
    }

    /**
     * Resolve the first and last name from whichever shape the caller sent.
     *
     * Explicit firstname/lastname win. Otherwise a single `name` is split on the last
     * space, so "Jane Doe" becomes firstname Jane / lastname Doe and a mononym like
     * "Kato" becomes firstname Kato / lastname null rather than being lost.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string|null, 1: string|null}
     */
    private function splitName(array $data): array
    {
        $first = trim((string) ($data['firstname'] ?? ''));
        $last = trim((string) ($data['lastname'] ?? ''));

        if ($first !== '') {
            return [$first, $last !== '' ? $last : null];
        }

        $full = trim((string) ($data['name'] ?? ''));

        if ($full === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+/', $full);
        $last = array_pop($parts);

        return [implode(' ', $parts) ?: $last, count($parts) ? $last : null];
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
        $oldRoleId = $user->role_id;

        $user->update([
            'email' => $validated['email'] ?? $user->email,
            'username' => isset($validated['username'])
                ? self::normalizeUsername($validated['username'])
                : $user->username,
            'role_id' => $validated['role_id'] ?? $user->role_id,
        ]);
        $worker = Worker::where('user_id', $user?->id)->first();
        if ($worker) {
            $worker->update([
                'firstname' => $validated['firstname'] ?? $worker->firstname,
                'lastname' => $validated['lastname'] ?? $worker->lastname,
                'nin' => $validated['nin'] ?? $worker->nin,
            ]);
        }

        if (isset($validated['role_id']) && $validated['role_id'] != $oldRoleId) {
            $oldRole = Role::find($oldRoleId);
            $newRole = Role::find($validated['role_id']);
            ActivityLog::create([
                'log_name' => 'Permission',
                'description' => 'User role changed',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'causer_type' => User::class,
                'causer_id' => Auth::id(),
                'properties' => [
                    'old' => ['role_id' => $oldRoleId, 'role_name' => $oldRole?->name],
                    'attributes' => ['role_id' => $validated['role_id'], 'role_name' => $newRole?->name],
                ],
            ]);
        }

        return $user->load('business', 'role');
    }

    /**
     * Delete a user
     */
}
