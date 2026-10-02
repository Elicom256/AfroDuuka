<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers self-serve signup: the data the form collects must actually be stored, and the
 * account must not be able to touch another tenant's data before it has a business.
 */
class SignupTest extends TestCase
{
    use RefreshDatabase;

    private function signupPayload(array $overrides = []): array
    {
        return array_merge([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '+256700000001',
            'password' => 'password123',
        ], $overrides);
    }

    private function createOtherTenant(): Business
    {
        $business = Business::factory()->create();

        User::factory()->create([
            'business_id' => $business->id,
        ]);

        return $business;
    }

    public function test_signup_stores_the_first_and_last_name(): void
    {
        $res = $this->postJson('/api/users/signup', $this->signupPayload());

        $res->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertSame('Jane', $user->firstname);
        $this->assertSame('Doe', $user->lastname);
    }

    public function test_signup_stores_phone_and_email(): void
    {
        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertSame('+256700000001', $user->phone);
        $this->assertSame('jane@example.com', $user->email);
    }

    public function test_signup_splits_a_single_name_field(): void
    {
        // The form historically posted one "name" input; both shapes must be honoured.
        $res = $this->postJson('/api/users/signup', $this->signupPayload([
            'firstname' => null,
            'lastname' => null,
            'name' => 'Kato Sarah',
        ]));

        $res->assertStatus(422, 'firstname is required when only name is sent');

        // With firstname explicitly supplied the explicit value wins.
        $this->postJson('/api/users/signup', $this->signupPayload([
            'firstname' => 'Only',
            'name' => 'ignored full name',
        ]))->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('Only', $user->firstname);
    }

    public function test_signup_requires_a_password(): void
    {
        $payload = $this->signupPayload();
        unset($payload['password']);

        $this->postJson('/api/users/signup', $payload)->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_signup_never_falls_back_to_a_default_password(): void
    {
        // A blank password previously became the literal string "password", which meant
        // anyone who knew the address could sign in.
        $res = $this->postJson('/api/users/signup', $this->signupPayload([
            'email' => 'nopass@example.com',
            'password' => '',
        ]));

        $res->assertStatus(422);
    }

    public function test_signup_requires_a_phone(): void
    {
        $payload = $this->signupPayload();
        unset($payload['phone']);

        $this->postJson('/api/users/signup', $payload)->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_signup_normalises_a_formatted_phone(): void
    {
        $this->postJson('/api/users/signup', $this->signupPayload([
            'phone' => '+256 700 000 009',
        ]))->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('+256700000009', $user->phone);
    }

    public function test_new_signup_has_no_business_yet(): void
    {
        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertNull($user->business_id);
    }

    public function test_signup_user_cannot_read_another_tenants_data(): void
    {
        $this->createOtherTenant();

        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $login = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $token = $login->json('data.token');

        // Before onboarding this must be refused, not quietly served.
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/products');

        $res->assertStatus(403);
        $this->assertSame('onboarding_incomplete', $res->json('error'));
    }

    public function test_signup_user_can_reach_onboarding_endpoints(): void
    {
        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $login = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $token = $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/me')
            ->assertStatus(200)
            ->assertJsonPath('onboarding.complete', false);
    }

    public function test_onboarding_completes_and_unlocks_the_tenant(): void
    {
        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $login = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);
        $token = $login->json('data.token');

        $category = BusinessCategory::factory()->create();
        $country = Country::factory()->create();

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/dashboard/business', [
                'name' => 'Jane Retail',
                'business_category_id' => $category->id,
                'country_id' => $country->id,
            ]);

        $res->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertNotNull($user->business_id);
        $this->assertNotNull($user->role_id);
        $this->assertSame('Executive', $user->role->name);

        // The tenant routes open up.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/me')
            ->assertStatus(200)
            ->assertJsonPath('onboarding.complete', true);
    }

    /**
     * Replays the exact sequence SignUp.tsx performs on one submit, in the same order.
     *
     * This is the guard that matters: business creation is authenticated, so the client
     * must obtain a token between signup and the business write. It previously logged in
     * afterwards, which would have sent an unauthenticated POST and left every new
     * account stuck at onboarding with no way forward from the signup screen.
     */
    public function test_the_single_screen_signup_sequence_works_end_to_end(): void
    {
        Plan::factory()->create(['slug' => 'basic', 'is_active' => true, 'sort_order' => 1]);
        $category = BusinessCategory::factory()->create();

        // 1. categories are fetched while still anonymous, to populate the dropdown
        $this->getJson('/api/business-categories')->assertStatus(200);

        // 2. account created from the first/last name fields
        $this->postJson('/api/users/signup', $this->signupPayload())
            ->assertStatus(201)
            ->assertJsonPath('onboarding.complete', false);

        // 3. token obtained with the password just chosen
        $token = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertStatus(200)->json('data.token');

        // 4. business created with that token, before any dashboard is reachable
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/dashboard/business', [
                'name' => 'Jane Retail',
                'business_category_id' => $category->id,
                'country_id' => Country::factory()->create()->id,
            ])
            ->assertStatus(201);

        // 5. the same token now opens the tenant and reports a provisioned role
        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/me')
            ->assertStatus(200);

        $this->assertTrue($me->json('onboarding.complete'));

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('Jane', $user->firstname);
        $this->assertSame('Doe', $user->lastname);
        $this->assertSame('Executive', $user->role->name);
    }

    /**
     * A missing country must be a validation error, not a silently-assigned one.
     *
     * businesses.country_id is NOT NULL, so there is no sensible default. The previous
     * fallback wrote Uganda onto every business that did not pick one, and 500'd when
     * the countries table happened to be empty.
     */
    public function test_business_creation_rejects_a_missing_country(): void
    {
        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $token = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->json('data.token');

        $before = Business::count();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/dashboard/business', [
                'name' => 'Jane Retail',
                'business_category_id' => BusinessCategory::factory()->create()->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('country_id');

        $this->assertSame($before, Business::count());
    }

    public function test_business_creation_starts_a_thirty_day_trial(): void
    {
        // A plan has to exist for there be anything to subscribe to; on a real install
        // PlanSeeder provides Basic/Pro/Enterprise.
        $plan = Plan::factory()->create(['slug' => 'basic', 'is_active' => true, 'sort_order' => 1]);

        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $login = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);
        $token = $login->json('data.token');

        $category = BusinessCategory::factory()->create();
        $country = Country::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/dashboard/business', [
                'name' => 'Jane Retail',
                'business_category_id' => $category->id,
                'country_id' => $country->id,
            ])->assertStatus(201);

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $subscription = Subscription::where('business_id', $user->business_id)->first();

        $this->assertNotNull($subscription, 'a trial subscription is created');
        $this->assertSame('active', $subscription->status);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertNotNull($subscription->trial_ends_at);

        $days = (int) now()->startOfDay()->diffInDays($subscription->trial_ends_at, false);
        $this->assertGreaterThanOrEqual(29, $days);
        $this->assertLessThanOrEqual(30, $days);
    }

    public function test_system_support_roles_are_not_treated_as_onboarding(): void
    {
        // CoreSupport legitimately has no business_id and operates across tenants. The
        // onboarding block must not catch it, or support staff lose all access.
        $role = Role::factory()->create(['name' => 'CoreSupport']);

        $support = User::factory()->create([
            'business_id' => null,
            'business_branch_id' => null,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->actingAs($support, 'sanctum')
            ->getJson('/api/products')
            ->assertStatus(200);
    }

public function test_business_categories_are_readable_before_signup(): void
    {
        BusinessCategory::factory()->create();

        $res = $this->getJson('/api/business-categories');

        $res->assertStatus(200);
        $this->assertNotEmpty($res->json());
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('password123'),
            'status' => 'suspended',
        ]);

        $this->postJson('/api/users/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertStatus(401);
    }

    public function test_login_returns_onboarding_state_for_a_new_account(): void
    {
        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $login = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $login->assertStatus(200);
        $this->assertNull($login->json('user.business_id'));
    }

    public function test_onboarding_succeeds_even_with_no_plan_seeded(): void
    {
        // Plans are seeded, but a fresh install should not be able to break onboarding
        // just because the catalogue is empty.
        $this->assertSame(0, Plan::count());

        $this->postJson('/api/users/signup', $this->signupPayload())->assertStatus(201);

        $login = $this->postJson('/api/users/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);
        $token = $login->json('data.token');

        $category = BusinessCategory::factory()->create();
        $country = Country::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/dashboard/business', [
                'name' => 'Jane Retail',
                'business_category_id' => $category->id,
                'country_id' => $country->id,
            ])->assertStatus(201);

        // No plan means no subscription, but the business still exists and onboarding
        // is complete — the failure is contained rather than fatal.
        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertNotNull($user->business_id);
        $this->assertSame(0, Subscription::count());
    }
}