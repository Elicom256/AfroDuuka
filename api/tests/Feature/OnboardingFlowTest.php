<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The four-step onboarding form as one request.
 *
 * SignupTest covers the account and the business separately. This covers the flow the
 * form actually performs: create the account, sign in with the password just chosen,
 * then create the business and its branches in a single call — and the values that call
 * used to drop on the way in.
 */
class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A token for an account with no business, which is the state the onboarding form
     * is in when it reaches the business step.
     */
    private string $token = '';

    private function onboardedUpToBusiness(array $accountOverrides = []): string
    {
        $account = array_merge([
            'firstname' => 'Amina',
            'lastname' => 'Nabirye',
            'email' => 'amina@example.com',
            'phone' => '+256700000001',
            'password' => 'password123',
            'username' => '@amina',
        ], $accountOverrides);

        $this->postJson('/api/users/signup', $account)->assertStatus(201);

        return $this->token = $this->postJson('/api/users/login', [
            'email' => $account['email'],
            'password' => $account['password'],
        ])->assertStatus(200)->json('data.token');
    }

    /**
     * Issue an authenticated request as this token.
     *
     * The Sanctum guard caches the user it resolved, and the test container is shared
     * by every request in a test method — so without dropping the guards, the request
     * after "create my business" is authenticated as the same in-memory User instance
     * that was loaded while the account still had no business and no role. Production
     * never sees this: a request boots its own container, and Octane flushes auth state
     * between them. BusinessService mutates that cached instance, which is why the
     * failure surfaced as a role check refusing a tenant's own owner rather than as an
     * authentication problem.
     */
    private function api(string $method, string $uri, array $data = [], ?string $token = null)
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(array_filter([
            'Authorization' => 'Bearer '.($token ?? $this->token),
            'Accept' => 'application/json',
        ]))->json($method, $uri, $data);
    }

    /**
     * The business this test created.
     *
     * Resolved by name rather than Business::first(). These tests roll their own rows
     * back, but a suite is not obliged to be empty when they run, and a test that only
     * passes when it happens to run first is a trap for whoever adds one after it.
     */
    private function createdBusiness(string $name = 'Amina Retail'): Business
    {
        return Business::where('name', $name)->firstOrFail();
    }

    private function branchesOf(Business $business): Collection
    {
        return BusinessBranch::where('business_id', $business->id)->get();
    }

    private function businessPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Amina Retail',
            'business_category_id' => BusinessCategory::factory()->create()->id,
            'country_id' => Country::factory()->create()->id,
        ], $overrides);
    }

    public function test_it_stores_the_business_email_and_phone_the_form_collected(): void
    {
        $token = $this->onboardedUpToBusiness();

        // Both were being dropped: StoreBusinessRequest declared no rules for them, so
        // validated() never returned them and the business silently inherited the
        // owner's contact details.
        $res = $this->api('post', '/api/dashboard/business', $this->businessPayload([
            'email' => 'sales@amina-retail.test',
            'phone' => '+256 700 222 333',
            'address' => 'Plot 1, Kampala Road',
        ]));

        $res->assertStatus(201)
            ->assertJsonPath('business.email', 'sales@amina-retail.test')
            ->assertJsonPath('business.address', 'Plot 1, Kampala Road');

        $business = $this->createdBusiness();

        // Normalised, because this value is the delivery address the welcome
        // notification is sent to and a spaced number is not deliverable.
        $this->assertSame('+256700222333', $business->phone);
    }

    public function test_it_falls_back_to_the_owners_details_when_the_business_omits_them(): void
    {
        $token = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload())->assertStatus(201, $token);

        $business = $this->createdBusiness();

        $this->assertSame('amina@example.com', $business->email);
        $this->assertSame('+256700000001', $business->phone);
    }

    public function test_it_creates_every_branch_the_form_collected(): void
    {
        $token = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload(['branches' => [['name' => 'Main Branch', 'address' => 'Plot 1', 'phone' => '+256 700 222 333'], ['name' => 'Nalugogo', 'address' => 'Plot 9', 'phone' => '0771234567']]]))->assertStatus(201, $token);

        $business = $this->createdBusiness();
        $branches = $this->branchesOf($business);

        $this->assertCount(2, $branches, 'exactly the branches asked for, no extra default');
        $this->assertSame(['Main Branch', 'Nalugogo'], $branches->pluck('name')->all());
        $this->assertSame('+256700222333', $branches->first()->phone);
    }

    /**
     * The name a business's first branch almost always gets, and the default the
     * column carries, used to collide with the branch the service created itself.
     */
    public function test_a_first_branch_called_main_branch_does_not_collide(): void
    {
        $token = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload(['branches' => [['name' => 'Main Branch', 'address' => 'Plot 1']]]))->assertStatus(201, $token);

        $this->assertCount(1, $this->branchesOf($this->createdBusiness()));
        $this->assertSame('Main Branch', $this->branchesOf($this->createdBusiness())->first()->name);
    }

    public function test_a_business_with_no_branches_still_gets_one(): void
    {
        $token = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload())->assertStatus(201, $token);

        $this->assertCount(1, $this->branchesOf($this->createdBusiness()));
        $this->assertSame('Main Branch', $this->branchesOf($this->createdBusiness())->first()->name);
    }

    /**
     * A branch name typed twice has to be a message the owner can act on. The unique
     * (business_id, name) index used to abort the request with a raw SQLSTATE after the
     * business row was already written.
     */
    public function test_two_branches_with_the_same_name_are_refused_and_nothing_is_written(): void
    {
        $token = $this->onboardedUpToBusiness();

        $res = $this->api('post', '/api/dashboard/business', $this->businessPayload([
            'branches' => [
                ['name' => 'Kampala', 'address' => 'Plot 1'],
                ['name' => 'kampala', 'address' => 'Plot 2'],
            ],
        ]));

        $res->assertStatus(422)->assertJsonValidationErrors('branches.1.name');

        $this->assertSame(0, Business::where('name', 'Amina Retail')->count(), 'a refused request leaves no tenant behind');
        $this->assertSame(0, BusinessBranch::where('name', 'Kampala')->count());
    }

    public function test_a_rejected_branch_list_rolls_the_whole_thing_back(): void
    {
        $token = $this->onboardedUpToBusiness();

        // The first branch is valid, the second has no name at all.
        $this->api('post', '/api/dashboard/business', $this->businessPayload(['branches' => [['name' => 'Kampala', 'address' => 'Plot 1'], ['name' => '', 'address' => 'Plot 2']]]))->assertStatus(422, $token);

        $this->assertSame(0, Business::where('name', 'Amina Retail')->count());
        $this->assertNull(User::where('email', 'amina@example.com')->firstOrFail()->business_id);
    }

    /**
     * Two shops in one plaza share an address, a trading centre shares a phone number.
     * None of those three columns identify a tenant, and the unique indexes that were on
     * them made the second business at any shared address fail with a 500.
     */
    public function test_two_businesses_can_share_an_address_and_a_phone(): void
    {
        $shared = [
            'address' => 'Plot 1, Kampala Road',
            'phone' => '+256700111222',
        ];

        $first = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload($shared))->assertStatus(201, $first);

        $second = $this->onboardedUpToBusiness([
            'firstname' => 'Ibrahim',
            'email' => 'ibrahim@example.com',
            'phone' => '+256700000002',
            'username' => '@ibrahim',
        ]);

        $this->api('post', '/api/dashboard/business', $this->businessPayload(array_merge($shared, ['name' => 'Ibrahim Retail'])))->assertStatus(201, $second);

        $this->assertSame(
            2,
            Business::whereIn('name', ['Amina Retail', 'Ibrahim Retail'])
                ->where('address', 'Plot 1, Kampala Road')
                ->count(),
            'both tenants kept the shared address'
        );
    }

    /**
     * The owner has to land in a branch of their own business. Without it the navbar's
     * branch badge could never render for anybody who signed themselves up, and every
     * branch-scoped read had to fall back to "all branches of the business".
     */
    public function test_the_owner_is_assigned_to_their_first_branch(): void
    {
        $token = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload(['branches' => [['name' => 'Kampala Road', 'address' => 'Plot 1'], ['name' => 'Nalugogo', 'address' => 'Plot 9']]]))->assertStatus(201, $token);

        $user = User::where('email', 'amina@example.com')->firstOrFail();
        $branch = $this->branchesOf($this->createdBusiness())->firstOrFail();

        $this->assertSame($branch->id, $user->business_branch_id);

        // And the API reports it under the key the navbar reads.
        $me = $this->api('get', '/api/users/me');

        $me->assertStatus(200)
            ->assertJsonPath('data.business_branch.name', 'Kampala Road')
            ->assertJsonPath('onboarding.complete', true);
    }

    public function test_the_username_is_stored_with_one_prefix(): void
    {
        $this->postJson('/api/users/signup', [
            'firstname' => 'Amina',
            'email' => 'amina@example.com',
            'phone' => '+256700000001',
            'password' => 'password123',
            // The signup form sends the "@" already; the service used to add another.
            'username' => '@amina',
        ])->assertStatus(201);

        $this->assertSame('@amina', User::where('email', 'amina@example.com')->firstOrFail()->username);
    }

    /**
     * The handle is derived from the first name, so the second Amina in the product
     * collides with the first. She must get a different handle, not a validation error
     * about a field she never typed.
     */
    public function test_a_duplicate_handle_is_made_unique_rather_than_rejected(): void
    {
        $this->postJson('/api/users/signup', [
            'firstname' => 'Amina',
            'email' => 'amina@example.com',
            'phone' => '+256700000001',
            'password' => 'password123',
            'username' => '@amina',
        ])->assertStatus(201);

        $this->postJson('/api/users/signup', [
            'firstname' => 'Amina',
            'email' => 'amina2@example.com',
            'phone' => '+256700000002',
            'password' => 'password123',
            'username' => '@amina',
        ])->assertStatus(201);

        $this->assertSame(2, User::whereIn('email', ['amina@example.com', 'amina2@example.com'])->count());
        $this->assertSame(2, User::where('username', 'like', '@amina%')->count());
    }

    public function test_a_user_can_sign_in_with_their_username(): void
    {
        $this->postJson('/api/users/signup', [
            'firstname' => 'Amina',
            'email' => 'amina@example.com',
            'phone' => '+256700000001',
            'password' => 'password123',
            'username' => '@amina',
        ])->assertStatus(201);

        foreach (['@amina', 'amina'] as $handle) {
            $this->postJson('/api/users/login', [
                'email' => $handle,
                'password' => 'password123',
            ])->assertStatus(200)->assertJsonPath('data.user.username', '@amina');
        }
    }

    public function test_a_branch_phone_is_accepted_in_the_formatted_shape_the_form_sends(): void
    {
        $token = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload())->assertStatus(201, $token);

        // Added after onboarding, the way the dashboard's own branch form does it.
        $this->api('post', '/api/dashboard/branches', [
            'name' => 'Nalugogo',
            'address' => 'Plot 9',
            // The branch form's own placeholder is this shape, and the old rule was
            // digits_between:10,10 — twelve digits, refused.
            'phone' => '+256 700 000 000',
        ], $token)->assertStatus(201);

        $this->assertSame('+256700000000', BusinessBranch::where('name', 'Nalugogo')->firstOrFail()->phone);
    }

    public function test_a_business_can_be_given_a_logo(): void
    {
        $token = $this->onboardedUpToBusiness();

        $this->api('post', '/api/dashboard/business', $this->businessPayload())->assertStatus(201, $token);

        // businesses.logo has been in the schema all along but was in neither $fillable
        // nor the update rules, so no request could set it and the navbar always fell
        // back to the platform wordmark.
        $this->api('patch', '/api/dashboard/business', [
            'name' => 'Amina Retail',
            'business_category_id' => BusinessCategory::firstOrFail()->id,
            'logo' => 'https://cdn.example.com/amina.png',
        ], $token)->assertStatus(200);

        $this->assertSame('https://cdn.example.com/amina.png', $this->createdBusiness()->logo);
    }
}
